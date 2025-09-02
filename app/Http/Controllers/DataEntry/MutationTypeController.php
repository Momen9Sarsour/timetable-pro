<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\MutationType;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class MutationTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $mutationTypes = MutationType::latest('mutation_id')->paginate(15);
            return view('dashboard.algorithm.mutation_types', compact('mutationTypes'));
        } catch (Exception $e) {
            Log::error("Error fetching mutation Types: " . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load mutation Methods page.');
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
            'name' => 'required|string|max:100|unique:mutation_types,name',
            'slug' => 'required|string|max:255|unique:mutation_types,slug|alpha_dash',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $dataToCreate = $validatedData;
            $dataToCreate['is_active'] = $request->has('is_active');
            MutationType::create($dataToCreate);
            return redirect()->route('algorithm-control.mutation-types.index')->with('success', 'mutation method created successfully.');
        } catch (Exception $e) {
            Log::error("mutation Type Creation Failed: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create mutation method.')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MutationType $mutationType)
    {
        $errorBagName = 'editMutationModal_' . $mutationType->mutation_id;
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('mutation_types')->ignore($mutationType->mutation_id, 'mutation_id')],
            'slug' => 'required|string|max:255|unique:mutation_types,slug,' . $mutationType->mutation_id . ',mutation_id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $dataToUpdate = $validatedData;
            $dataToUpdate['is_active'] = $request->has('is_active');
            $mutationType->update($dataToUpdate);
            return redirect()->route('algorithm-control.mutation-types.index')->with('success', 'Mutation method updated successfully.');
        } catch (Exception $e) {
            Log::error("Mutation Type Update Failed for ID {$mutationType->mutation_id}: " . $e->getMessage());
            return redirect()->back()->withErrors(['update_error' => 'Failed to update mutation method.'], $errorBagName)->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MutationType $mutationType)
    {
        try {
            // (اختياري) التحقق إذا كانت الطريقة مستخدمة في أي عملية تشغيل سابقة
            // if ($mutationType->populations()->exists()) {
            //     return redirect()->route('algorithm-control.mutation-types.index')->with('error', 'Cannot delete: This method is used in historical data.');
            // }
            $mutationType->delete();
            return redirect()->route('algorithm-control.mutation-types.index')->with('success', 'Mutation method deleted successfully.');
        } catch (Exception $e) {
            Log::error("Mutation Type Deletion Failed for ID {$mutationType->mutation_id}: " . $e->getMessage());
            return redirect()->route('algorithm-control.mutation-types.index')->with('error', 'Failed to delete mutation method.');
        }
    }
}
