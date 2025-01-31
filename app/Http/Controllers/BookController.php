<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Book;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function landing()
    {
        $books = Book::orderBy('id', 'asc')->paginate(6);
        return view('landing', compact('books'));
    }
    public function addComment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $book = Book::find($id);

        if (!$book) {
            return redirect()->route('landing')->with('error', 'El libro no se encontró.');
        }

        $comment = new Comment();
        $comment->content = $request->input('content');
        $comment->user_id = Auth::id();
        $comment->book_id = $book->id;
        $comment->save();

        return redirect()->back()->with('success', 'Comentario agregado correctamente.');
    }

    public function deleteComment($bookId, $commentId)
    {
        $comment = Comment::find($commentId);

        if (!$comment) {
            return redirect()->back()->with('error', 'El comentario no se encontró.');
        }

        // Verificar si el usuario tiene permiso para eliminar el comentario
        if ($comment->user_id === Auth::id() || Auth::user()->isAdmin()) {
            $comment->delete();
            return redirect()->back()->with('success', 'Comentario eliminado correctamente.');
        }

        return redirect()->back()->with('error', 'No tienes permiso para eliminar este comentario.');
    }

    public function show($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return redirect()->route('landing')->with('error', 'El libro no se encontró.');
        }

        // Obtener todos los comentarios del libro
        $comments = Comment::where('book_id', $book->id)->get();

        return view('show', compact('book', 'comments'));
    }
    public function index()
    {
        $books = Auth::user()->books()->paginate(6);
        return view('mybooks.index', compact('books'));
    }

    public function create()
    {
        return view('mybooks.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required',
            'descripcion' => ['required', 'string', 'min:50'],
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif',
            'url' => 'required|url',
        ], [
            'titulo.required' => 'El título del libro es obligatorio.',
            'descripcion.required' => 'La descripción del libro es obligatoria.',
            'descripcion.min' => 'La descripción del libro debe tener al menos 50 palabras.',
            'imagen.required' => 'La imagen del libro es obligatoria.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg o gif.',
            'url.required' => 'La URL del libro es obligatoria.',
            'url.url' => 'La URL proporcionada no es válida.',
        ]);

        $imageName = time().'.'.$request->imagen->extension();
        $request->imagen->move(public_path('images'), $imageName);
    
        $book = new Book([
            'titulo' => $request->get('titulo'),
            'descripcion' => $request->get('descripcion'),
            'imagen' => 'images/'.$imageName,
            'url' => $request->get('url'),
            'user_id' => Auth::id(),
        ]);
        $book->save();
    
        return redirect()->route('mybooks.index')->with('success', 'El libro ha sido añadido correctamente.');
    }

    public function edit(Book $book)
    {
        return view('mybooks.edit', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $request->validate([
            'titulo' => 'required',
            'descripcion' => ['required', 'string', 'min:50'],
            'imagen' => 'image|mimes:jpeg,png,jpg,gif',
            'url' => 'required|url',
        ], [
            'titulo.required' => 'El título del libro es obligatorio.',
            'descripcion.required' => 'La descripción del libro es obligatoria.',
            'descripcion.min' => 'La descripción del libro debe tener al menos 50 palabras.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg o gif.',
            'url.required' => 'La URL del libro es obligatoria.',
            'url.url' => 'La URL proporcionada no es válida.',
        ]);

        $book->titulo = $request->input('titulo');
        $book->descripcion = $request->input('descripcion');
        $book->url = $request->input('url');

        if ($request->hasFile('imagen')) {
            if (!is_null($book->imagen)) {
                Storage::delete($book->imagen);
            }
            
            $imageName = time().'.'.$request->imagen->extension();
            $request->imagen->move(public_path('images'), $imageName);
            $book->imagen = 'images/'.$imageName;
        }

        $book->save();

        return redirect()->route('mybooks.index')->with('success', 'El libro ha sido actualizado correctamente.');
    }

    public function destroy(Book $book)
    {
        Storage::delete($book->imagen);
        $book->delete();

        return redirect()->route('mybooks.index')->with('success', 'El libro ha sido eliminado correctamente.');
    }
}
