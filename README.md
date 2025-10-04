# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Timetable Management System** built with Laravel that uses **genetic algorithms** to automatically generate optimal class schedules for universities. The system manages academic entities (departments, instructors, subjects, rooms, sections) and applies sophisticated algorithms to solve the complex timetable scheduling problem.

## Development Commands

### Environment Setup
```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Generate application key
php artisan key:generate

# Copy environment file
cp .env.example .env

# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed
# Available seeders: Departments, Roles, Users, Rooms, RoomTypes, Subjects, SubjectTypes, SubjectCategories, Instructors, Plans, PlanSubjects, Timeslots
```

### Development Server
```bash
# Start Laravel development server
php artisan serve

# Start Vite for frontend assets
npm run dev

# Build frontend assets for production
npm run build
```

### Testing & Quality
```bash
# Run tests
php artisan test

# Clear application caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Run queue workers (for genetic algorithm jobs)
php artisan queue:work
```

## Core Architecture

### Database Schema Architecture
The system uses a normalized relational database with the following key entity relationships:

- **Academic Structure**: Plans → Plan Subjects → Sections
- **Resources**: Instructors, Rooms (with Types), Timeslots
- **Algorithm Core**: Populations → Chromosomes → Genes
- **Configuration**: CrossoverTypes, SelectionTypes, MutationTypes

### Genetic Algorithm Flow
1. **Data Loading**: Load sections, instructors, rooms, timeslots for specific academic year/semester
2. **Population Creation**: Generate initial population of chromosomes (timetables)
3. **Fitness Evaluation**: Calculate penalties for conflicts (student, teacher, room, capacity, type mismatches)
4. **Evolution**: Apply selection, crossover, and mutation operators
5. **Result Storage**: Store best solutions and provide visualization

### Key Components

**Core Services:**
- `GeneticAlgorithmService`: Main algorithm implementation with fitness evaluation, selection (tournament), crossover (single-point), and mutation (smart swap)
- `ConflictCheckerService`: Validates timetable constraints

**Main Controllers:**
- `TimetableGenerationController`: Initiates algorithm runs via background jobs
- `TimetableResultController`: Displays and manages generated schedules
- `TimetableViewController`: Provides different timetable views (by section, instructor, room)
- `NewGeneticController`, `NewPopulationController`: Controllers for the newer modular algorithm implementation

**Data Entry Controllers**: Comprehensive CRUD operations for all academic entities in `DataEntry/` namespace

**Background Jobs:**
- `GenerateTimetableJob`: Main job for running genetic algorithm in background
- `ContinueEvolutionJob`: Resume evolution from existing population
- `GenerateInitialPopulationJob`: Create initial population separately

### Job Queue System
The genetic algorithm runs as background jobs to prevent blocking the web interface.

**Production setup:**
1. Set `QUEUE_CONNECTION=database` in `.env`
2. Run migrations to create jobs table (included)
3. Start queue worker:
```bash
php artisan queue:work --daemon
# Or with supervisor for auto-restart
php artisan queue:listen
```

## Key Models and Relationships

**Academic Hierarchy:**
- `Plan` → `PlanSubject` → `Section`
- `Instructor` ↔ `Subject` (many-to-many)
- `Section` ↔ `Instructor` (many-to-many through section assignments)

**Algorithm Models:**
- `Population` (algorithm run) → `Chromosome` (timetable solution) → `Gene` (individual class assignment)
- Each Gene contains: section_id, instructor_id, room_id, timeslot_ids, student_group_id

## Configuration

### Environment Variables
Key settings in `.env`:
- Database connection (MySQL recommended)
- **Queue connection**: Set `QUEUE_CONNECTION=database` (or `redis`) in production for background jobs. Default is `sync` for development.
- Application timezone and locale

### Algorithm Settings
Configurable through the web interface:
- Population size (10-500)
- Max generations (10-10,000)
- Mutation rate (0-1)
- Crossover rate (0-1)
- Selection tournament size
- Theory/practical credit-to-slot ratios

## File Structure Notes

### Controllers Organization
- `Algorithm/`: Timetable generation and result management
- `DataEntry/`: CRUD operations for all academic entities

### Views Structure
- `dashboard/algorithm/`: Algorithm controls and result displays
- `dashboard/data-entry/`: Forms and tables for data management
- `dashboard/timetables/`: Timetable viewing interfaces

### Services
- `GeneticAlgorithmService.php`: Core genetic algorithm implementation (~1330 lines)
- Contains sophisticated optimization including resource conflict detection and intelligent mutation strategies
- **New Modular Architecture**: The system has a newer implementation split into specialized services:
  - `GeneticAlgorithmServiceNew.php`: Refactored main algorithm controller
  - `FitnessCalculatorServiceNew.php`: Dedicated fitness evaluation
  - `PopulationGeneratorServiceNew.php`: Initial population creation
  - `SectionGeneratorServiceNew.php`: Section scheduling logic
  - `PopulationSaveServiceNew.php`: Database persistence
  - `InitialPopulationService.php`, `ContinueEvolutionService.php`: Job-specific services

## Development Guidelines

### Algorithm Architecture Notes
The codebase contains **two implementations**:
1. **Original**: `GeneticAlgorithmService` - Monolithic service with all logic
2. **New/Modular**: `*ServiceNew.php` files - Separated concerns for better maintainability

Both implementations coexist. The newer modular approach is recommended for new features.

### Adding New Algorithm Features
- Extend `GeneticAlgorithmService` (or corresponding `*ServiceNew` module) for new genetic operators
- Add corresponding database types in `CrossoverTypes`, `SelectionTypes`, or `MutationTypes`
- Update the settings interface accordingly
- New controllers: `NewGeneticController` and `NewPopulationController`

### Data Model Extensions
- Follow the existing pattern of CRUD controllers in `DataEntry/`
- Maintain consistent validation rules and bulk upload capabilities
- All data entry controllers support bulk Excel upload via `maatwebsite/excel`
- Update database relationships carefully as they impact algorithm performance

### Performance Considerations
- The genetic algorithm heavily uses database transactions and bulk operations
- Resource usage caching is implemented to improve fitness evaluation performance
- Consider database indexing on frequently queried fields (population_id, chromosome_id, etc.)
- Long-running algorithms must use queue workers to avoid timeouts
