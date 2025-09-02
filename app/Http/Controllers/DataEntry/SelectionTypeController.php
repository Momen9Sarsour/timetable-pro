<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\SelectionType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SelectionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $selectionTypes = SelectionType::latest('selection_type_id')->paginate(15);
            return view('dashboard.algorithm.selection-types', compact('selectionTypes'));
        } catch (Exception $e) {
            Log::error("Error fetching Selection Types: " . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load Selection Methods page.');
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
            'name' => 'required|string|max:100|unique:selection_types,name',
            'slug' => 'required|string|max:255|unique:selection_types,slug|alpha_dash',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $dataToCreate = $validatedData;
            $dataToCreate['is_active'] = $request->has('is_active');
            SelectionType::create($dataToCreate);
            return redirect()->route('algorithm-control.selection-types.index')->with('success', 'Selection method created successfully.');
        } catch (Exception $e) {
            Log::error("Selection Type Creation Failed: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create selection method.')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SelectionType $selectionType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SelectionType $selectionType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SelectionType $selectionType)
    {
        // لاحظ أن اسم المتغير هو $selectionType كما يولده Laravel
        $errorBagName = 'editSelectionModal_' . $selectionType->selection_type_id;
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('selection_types')->ignore($selectionType->selection_type_id, 'selection_type_id')],
            'slug' => 'required|string|max:255|unique:selection_types,slug,' . $selectionType->selection_type_id . ',selection_type_id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $dataToUpdate = $validatedData;
            $dataToUpdate['is_active'] = $request->has('is_active');
            $selectionType->update($dataToUpdate);
            return redirect()->route('algorithm-control.selection-types.index')->with('success', 'Selection method updated successfully.');
        } catch (Exception $e) {
            Log::error("Selection Type Update Failed for ID {$selectionType->selection_type_id}: " . $e->getMessage());
            return redirect()->back()->withErrors(['update_error' => 'Failed to update selection method.'], $errorBagName)->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SelectionType $selectionType)
    {
        try {
            // (اختياري) التحقق إذا كانت الطريقة مستخدمة
            // if ($selectionType->populations()->exists()) {
            //     return redirect()->route('algorithm-control.selection-types.index')->with('error', 'Cannot delete: This method is used in historical data.');
            // }
            $selectionType->delete();
            return redirect()->route('algorithm-control.selection-types.index')->with('success', 'Selection method deleted successfully.');
        } catch (Exception $e) {
            Log::error("Selection Type Deletion Failed for ID {$selectionType->selection_type_id}: " . $e->getMessage());
            return redirect()->route('algorithm-control.selection-types.index')->with('error', 'Failed to delete selection method.');
        }
    }
}
