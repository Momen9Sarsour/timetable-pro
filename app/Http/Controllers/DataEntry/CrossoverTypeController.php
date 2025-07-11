<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\CrossoverType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CrossoverTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $crossoverTypes = CrossoverType::latest('crossover_id')->paginate(15);
            return view('dashboard.algorithm.crossover-types', compact('crossoverTypes'));
        } catch (Exception $e) {
            Log::error("Error fetching Crossover Types: " . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load Crossover Methods page.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100|unique:crossover_types,name',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $dataToCreate = $validatedData;
            $dataToCreate['is_active'] = $request->has('is_active');
            CrossoverType::create($dataToCreate);
            return redirect()->route('algorithm-control.crossover-types.index')->with('success', 'Crossover method created successfully.');
        } catch (Exception $e) {
            Log::error("Crossover Type Creation Failed: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create crossover method.')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CrossoverType $crossoverType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CrossoverType $crossoverType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CrossoverType $crossoverType)
    {
        $errorBagName = 'editCrossoverModal_' . $crossoverType->crossover_id;
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('crossover_types')->ignore($crossoverType->crossover_id, 'crossover_id')],
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $dataToUpdate = $validatedData;
            $dataToUpdate['is_active'] = $request->has('is_active');
            $crossoverType->update($dataToUpdate);
            return redirect()->route('algorithm-control.crossover-types.index')->with('success', 'Crossover method updated successfully.');
        } catch (Exception $e) {
            Log::error("Crossover Type Update Failed for ID {$crossoverType->crossover_id}: " . $e->getMessage());
            return redirect()->back()->withErrors(['update_error' => 'Failed to update crossover method.'], $errorBagName)->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CrossoverType $crossoverType)
    {
        try {
            // (اختياري) التحقق إذا كانت الطريقة مستخدمة في أي عملية تشغيل سابقة
            // if ($crossoverType->populations()->exists()) {
            //     return redirect()->route('algorithm-control.crossover-types.index')->with('error', 'Cannot delete: This method is used in historical data.');
            // }
            $crossoverType->delete();
            return redirect()->route('algorithm-control.crossover-types.index')->with('success', 'Crossover method deleted successfully.');
        } catch (Exception $e) {
            Log::error("Crossover Type Deletion Failed for ID {$crossoverType->crossover_id}: " . $e->getMessage());
            return redirect()->route('algorithm-control.crossover-types.index')->with('error', 'Failed to delete crossover method.');
        }
    }
}
