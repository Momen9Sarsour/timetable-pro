@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Genetic Algorithm Settings</h1>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Configure Parameters</h5>
            </div>
             <form action="{{ route('algorithm.settings.save') }}" method="POST">
                @csrf
                <div class="card-body">
                    <p class="card-text text-muted mb-4">Adjust the parameters for the timetable generation process. Different settings can affect the speed and quality of the resulting schedule.</p>

                    <div class="row">
                        {{-- إعدادات الأعداد --}}
                        <div class="col-md-4 mb-3">
                            <label for="population_size" class="form-label">Population Size</label>
                            <input type="number" class="form-control @error('population_size') is-invalid @enderror" id="population_size" name="population_size" value="{{ old('population_size', $settings['population_size']) }}" min="10" step="10">
                            <small class="text-muted">Number of schedules (chromosomes) in each generation. (e.g., 50-200)</small>
                            @error('population_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_generations" class="form-label">Maximum Generations</label>
                            <input type="number" class="form-control @error('max_generations') is-invalid @enderror" id="max_generations" name="max_generations" value="{{ old('max_generations', $settings['max_generations']) }}" min="10" step="100">
                            <small class="text-muted">The maximum number of generations to run before stopping. (e.g., 500-2000)</small>
                             @error('max_generations') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="mutation_rate" class="form-label">Mutation Rate</label>
                            <input type="number" class="form-control @error('mutation_rate') is-invalid @enderror" id="mutation_rate" name="mutation_rate" value="{{ old('mutation_rate', $settings['mutation_rate']) }}" min="0" max="1" step="0.01">
                            <small class="text-muted">Probability of random changes in a schedule. (e.g., 0.01 for 1%)</small>
                             @error('mutation_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        {{-- إعدادات العمليات --}}
                        <div class="col-md-6 mb-3">
                             <label for="crossover_type_id" class="form-label">Crossover Method</label>
                             <select class="form-select @error('crossover_type_id') is-invalid @enderror" id="crossover_type_id" name="crossover_type_id" required>
                                 @foreach($crossoverTypes as $type)
                                     <option value="{{ $type->crossover_id }}" title="{{ $type->description }}" {{ old('crossover_type_id', $settings['crossover_type_id']) == $type->crossover_id ? 'selected' : '' }}>
                                         {{ $type->name }}
                                     </option>
                                 @endforeach
                             </select>
                             <small class="text-muted">The method used to combine two parent schedules to create offspring.</small>
                             @error('crossover_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                             <label for="selection_type_id" class="form-label">Selection Method</label>
                             <select class="form-select @error('selection_type_id') is-invalid @enderror" id="selection_type_id" name="selection_type_id" required>
                                 @foreach($selectionTypes as $type)
                                     <option value="{{ $type->selection_type_id }}" title="{{ $type->description }}" {{ old('selection_type_id', $settings['selection_type_id']) == $type->selection_type_id ? 'selected' : '' }}>
                                         {{ $type->name }}
                                     </option>
                                 @endforeach
                             </select>
                             <small class="text-muted">The method used to select the "fittest" schedules for the next generation.</small>
                             @error('selection_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                     <hr class="my-4">

                      <div class="row">
                        <div class="col-md-12">
                             <div class="form-check form-switch">
                               <input class="form-check-input" type="checkbox" role="switch" id="stop_at_first_valid" name="stop_at_first_valid" value="1" {{ old('stop_at_first_valid', $settings['stop_at_first_valid']) ? 'checked' : '' }}>
                               <label class="form-check-label" for="stop_at_first_valid">Stop at First Valid Solution</label>
                               <small class="text-muted d-block">If checked, the process will stop as soon as it finds a schedule with zero hard-constraint violations, even if it's not the absolute best.</small>
                             </div>
                        </div>
                      </div>

                </div>
                 <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
