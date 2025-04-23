<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BooksController extends Controller
{
    function index(Request $request)
    {
        if ($request->has('q')) {
            $books = Book::where('name', 'like', '%' . $request->q . '%')
                ->paginate($request->count);
        } else {
            $books = Book::latest('id')->paginate(20);
        }

        return view('books.index', compact('books'));
    }

    function create()
    {
        return view('books.create');
    }

    function store(Request $request)
    {
        // validate data
        $request->validate([
            'name' => 'required',
            'publisher' => 'required',
            'page_count' => 'required',
            'price' => 'required'
        ]);

        $data = $request->except('_token', 'cover');

        // upload files
        if ($request->hasFile('cover')) {
            $cover_name = rand() . time() . $request->file('cover')->getClientOriginalName();
            $request->file('cover')->move(public_path('uploads/covers'), $cover_name);
            $data['cover'] = $cover_name;
        }

        // add to database
        Book::create($data);

        // redirect to any page
        return redirect()
            ->route('books.index')
            ->with('msg', 'Book added successfully')
            ->with('type', 'success');
    }

    function update(Request $request, $id)
    {

        // validate data
        $request->validate([
            'name' => 'required',
            'publisher' => 'required',
            'page_count' => 'required',
            'price' => 'required'
        ]);

        $data = $request->except('_token', 'cover');

        $book = Book::find($id);
        // upload files
        if ($request->hasFile('cover')) {
            $cover_name = rand() . time() . $request->file('cover')->getClientOriginalName();
            $request->file('cover')->move(public_path('uploads/covers'), $cover_name);
            $data['cover'] = $cover_name;
            if ($book->cover != 'no-image.png') {
                File::delete(public_path('uploads/covers/' . $book->cover));
            }
        }

        $book->update($data);

        return $book;

        // // redirect to any page
        // return redirect()
        // ->route('books.index')
        // ->with('msg', 'Book updated successfully')
        // ->with('type', 'success');
    }

    function trash(Request $request)
    {
        if ($request->has('q')) {
            $books = Book::onlyTrashed()->where('name', 'like', '%' . $request->q . '%')
                ->paginate($request->count);
        } else {
            $books = Book::onlyTrashed()->latest('id')->paginate(20);
        }
        return view('books.trash', compact('books'));
    }

    function restore($id)
    {
        $book = Book::onlyTrashed()->find($id);

        $book->restore();

        return redirect()
            ->route('books.trash')
            ->with('msg', 'Book restored successfully')
            ->with('type', 'info');
    }

    function destroy($id)
    {
        Book::destroy($id);

        return redirect()
            ->route('books.index')
            ->with('msg', 'Book deleted successfully')
            ->with('type', 'warning');
    }

    function forcedelete($id)
    {
        $book = Book::onlyTrashed()->find($id);

        if ($book->cover != 'no-image.png') {
            File::delete(public_path('uploads/covers/' . $book->cover));
        }

        $book->forceDelete();

        return redirect()
            ->route('books.trash')
            ->with('msg', 'Book deleted permanently successfully')
            ->with('type', 'info');
    }

    function delete_selected(Request $request)
    {
        if ($request->selected_ids == 'all') {
            DB::table('books')->update(['deleted_at' => now()]);
        } else {
            $ids = explode(',', $request->selected_ids);
            Book::whereIn('id', $ids)->delete();
        }

        return redirect()
            ->route('books.index')
            ->with('msg', 'Selected Books deleted successfully')
            ->with('type', 'success');
    }
}
