<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookImages;
use Illuminate\Http\Request;
use App\Models\UserBookProgress;
use App\Http\Requests\BookRequest;
use App\Models\Chapter;
use App\Models\PDF;

class BookController extends Controller
{

    public function __construct(protected Book $book, protected UserBookProgress $userBookProgress, protected Chapter $chapter){}

    // Track When a User Starts Reading a Book
    public function startReading($bookId)
    {
        $book = $this->book->find($bookId);
        $chapter = $book->chapters->first();
        // dd($book->chapters);
        // dd($chapter->images);

        // Check if the book exists
        if (!$book) {
            return redirect()->back()->with('error', 'Book not found.');
        }

        if (auth()->check()) {
            $user = auth()->user();
        
            $progress = $this->userBookProgress->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'book_id' => $bookId,
                ],
                [
                    'last_page_read' => 1,
                    'is_completed' => false,
                ]
            );
        } else {
            // Store reading progress in session for guest users
            session()->put("reading_books.{$bookId}", ['last_page_read' => 1]);
        }


        // Check if the book has a PDF path
            if (!empty($pdf = $book->pdfs->first())) {
                
                // Pass the PDF to the view for rendering
                return view('readingbook', [
                    'book' => $book,
                    'pdf' => $book->pdf,
                    'chapter' => $pdf->chapter,
                    'images' => null // Optional: pass null if not needed
                ]);
            }

        // Otherwise, load the images
       
         $images = $chapter->images()->paginate(50);

         $firstImage = $images->first();

         $chapter = $firstImage->chapter;

        // Render the image-based reading view
        return view('readingbook', [
            'book' => $book,
            'images' => $images,
            'chapter' => $chapter,
            'pdf' => null
        ]);
    }

    public function readChapter($bookId){
        $book = $this->book->find($bookId);
        $chapterRequest=$this->chapter->find(request('chapter_id'));
        $chapter = $book->chapters()->where('id', $chapterRequest->id)->firstOrFail();
        // dd($chapter);
        $images = $chapter->images()->paginate(50);

        return view('readingbook', [
            'book' => $book,
            'images' => $images,
            'chapter' => $chapter,
            'pdf' => null
        ]);

    }

    public function viewAddChapter($bookId){
        $book = $this->book->find($bookId);
        return view("admin.add_chapter", ["book" => $book]);
    }

    public function addChapter(Request $request, $bookId){
        $book = $this->book->find($bookId);
        $pdfPath = null;
        $uploadImages = false;

        $chapterRequest = $request->validate([
            "chapter" =>"required",
            "images" => "array|required",
        ]);

        $chapter = $book->chapters()->create([
            'name' => $chapterRequest['chapter'], 
            'book_id' => $book->id
        ]);

         if ($request->hasFile('pdf_file')) {

            $pdfPath = $request->file('pdf_file')->store('pdfs', 'public');
            $pdfPath = 'storage/' . $pdfPath;
            PDF::create([
                    'book_id' => $book->id,
                    'chapter_id' => $chapter->id,
                    'pdf_path'   => 'storage/' . $pdfPath,
                ]);
        } elseif ($request->hasFile('images')) {
            $uploadImages = true; 
        }

        
        if ($uploadImages) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('images', 'public');

                BookImages::create([
                    'book_id' => $book->id,
                    'chapter_id' => $chapter->id,
                    'image'   => 'storage/' . $imagePath,
                ]);
            }
        }

        return redirect()->route("book.show")->with('success', 'Added successfully!');

        
    }

    public function adminReadBook($bookId)
    {
        $book = $this->book->find($bookId);

        // Check if the book exists
        if (!$book) {
            return redirect()->back()->with('error', 'Book not found.');
        }

        // Store reading progress for authenticated users
        if (auth()->check()) {
            $user = auth()->user();
        
            $progress = $this->userBookProgress->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'book_id' => $bookId,
                ],
                [
                    'last_page_read' => 1,
                    'is_completed' => false,
                ]
            );
        } else {
            // Store reading progress in session for guest users
            session()->put("reading_books.{$bookId}", ['last_page_read' => 1]);
        }

        // Check if the book has a PDF path
        if (!empty($book->pdf_path)) {
            // Pass the PDF to the view for rendering
            return view('admin_readbook', [
                'book' => $book,
                'pdf' => $book->pdf_path,
                'images' => null // Optional: pass null if not needed
            ]);
        }

        // Otherwise, load the images
        $images = $book->images;

        // Render the image-based reading view
        return view('admin_readbook', [
            'book' => $book,
            'images' => $images,
            'pdf' => null
        ]);
    }

    


    // Fetch Books for Continue Reading 
    public function continueReading()
    {
        if (auth()->check()) {
            // Logged-in users: Get books from database
            $user = auth()->user();
            $books = $user->readingBooks()->get();
        } else {
            // Guest users: Get book data from session
            $bookIds = array_keys(session('reading_books', [])); // Get stored book IDs
            $books = Book::whereIn('id', $bookIds)->get(); // Fetch book details
        }

        return view('books.continue-reading', compact('books'));
    }

    public function createBook(BookRequest $request)
    {
        $bookRequest = $request->validated();

        // Store Cover Picture, PDF, or Images
        $coverPath = null;
        $pdfPath = null;
        $uploadImages = false;

        if ($request->hasFile('cover_picture')) {
            $coverPath = $request->file('cover_picture')->store('covers', 'public');
            $coverPath = 'storage/' . $coverPath;
        }


        // Create Book Record
        $book = $this->book->create([
            'title'         => $bookRequest['title'],
            'author'        => $bookRequest['author'],
            'cover_picture' => $coverPath,
            // 'pdf_path'      => $pdfPath,
            'category1'     => $bookRequest['category1'],
            'category2'     => $bookRequest['category2'] ?? null,
            'status'        => $bookRequest['status'],
            'day'           => $bookRequest['day'],
            'age_limit'     => (int) $bookRequest['age_limit'],
            'new_arrival'   => isset($bookRequest['new_arrival']) ? (bool) $bookRequest['new_arrival'] : false,
        ]);

        
        $chapter = Chapter::create([
            'name' => $bookRequest['chapter'], 
            'book_id' => $book->id
        ]);


        if ($request->hasFile('pdf_file')) {

            $pdfPath = $request->file('pdf_file')->store('pdfs', 'public');
            $pdfPath = 'storage/' . $pdfPath;
            PDF::create([
                    'book_id' => $book->id,
                    'chapter_id' => $chapter->id,
                    'pdf_path'   => 'storage/' . $pdfPath,
                ]);
        } elseif ($request->hasFile('images')) {
            $uploadImages = true; // Only allow image upload if no PDF is present
        }

        // Save additional images only if PDF is not uploaded
        if ($uploadImages) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('images', 'public');

                BookImages::create([
                    'book_id' => $book->id,
                    'chapter_id' => $chapter->id,
                    'image'   => 'storage/' . $imagePath,
                ]);
            }
        }

        return redirect()->route('book.show')->with('success', 'Comic added successfully!');
    }

    public function editBook($bookId){
        $book = $this->book->find($bookId);

        if(!$book){
            return redirect()->back()->with('error', 'Book Not found');
        }

        return view('admin.edit_book', compact('book'));
    }


    public function editBookImages($bookId){
        $book = $this->book->find($bookId);
        $chapter = $this->chapter->find(request('chapter_id'));
        $bookImages = $book->images;

        if(!$book){
            return redirect()->back()->with('error', 'Book Not found');
        }

        return view('admin.bookImages_edit',['book'=> $book, 'chapter'=> $chapter, 'Images' => $bookImages]);
    }

    public function updateBook(Request $bookRequest, $bookId){
        $validatedData = $bookRequest->validate([
            'title'        => 'required|string|max:255',
            'author'       => 'required|string|max:255',
            'cover_picture'=> 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category1'    => 'required|string',
            'category2'    => 'nullable|string',
            'day'          => 'required|string',
            'age_limit'    => 'required|integer',
            'status'       => 'required|boolean',
            'new_arrival'  => 'nullable|boolean',
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'deleted_images' => 'nullable|array',
        ]);
        $book = $this->book->findOrFail($bookId);

        $coverPath = $book->cover_picture; 
        if ($bookRequest->hasFile('cover_picture')) {
            // Delete old cover if exists
            if ($book->cover_picture && file_exists(public_path($book->cover_picture))) {
                unlink(public_path($book->cover_picture));
            }
            // Store new cover
            $coverPath = $bookRequest->file('cover_picture')->store('covers', 'public');
            $coverPath = 'storage/' . $coverPath;
        }

        // Update Book Record
        $book->update([
            'title'        => $bookRequest['title'],
            'author'       => $bookRequest['author'],
            'cover_picture'=> $coverPath,
            'category1'    => $bookRequest['category1'],
            'category2'    => $bookRequest['category2'] ?? null,
            'day'          => $bookRequest['day'],
            'age_limit'    => (int) $bookRequest['age_limit'],
            'new_arrival'  => isset($bookRequest['new_arrival']) ? (bool) $bookRequest['new_arrival'] : false,
            'status'  => isset($bookRequest['status']) ? (bool) $bookRequest['status'] : true,
            ]);


            return redirect()->route('book.show')->with('success', 'Comic updated successfully!');
    }

    public function updateBookImages(Request $bookRequest, $bookId){

        $book = $this->book->findOrFail($bookId);
        // dd($bookRequest->chapter);
        
        // ✅ HANDLE IMAGE DELETIONS
        if ($bookRequest->has('deleted_images')) {
            foreach ($bookRequest->input('deleted_images') as $deletedImage) {
                $bookImage = BookImages::where('book_id', $book->id)->where('image', $deletedImage)->first();
                if ($bookImage) {
                    if (file_exists(public_path($bookImage->image))) {
                        unlink(public_path($bookImage->image)); // Delete from storage
                    }
                    $bookImage->delete(); // Delete from DB
                }
            }
        }
    
        // ✅ HANDLE NEW IMAGE UPLOADS
        if ($bookRequest->hasFile('images')) {
            foreach ($bookRequest->file('images') as $image) {
                $imagePath = $image->store('images', 'public');
    
                BookImages::create([
                    'book_id' => $book->id,
                    'chapter_id' =>$bookRequest->chapter ,
                    'image'   => 'storage/' . $imagePath,
                ]);
            }
        }
    
        return redirect()->route('book.show')->with('success', 'Images updated successfully!');
    }


    public function deleteBook($bookId)
    {
        $book = $this->book->findOrFail($bookId);

        // Delete cover picture if exists
        if ($book->cover_picture && file_exists(public_path($book->cover_picture))) {
            unlink(public_path($book->cover_picture));
        }

        // Delete associated images
        $bookImages = BookImages::where('book_id', $book->id)->get();
        foreach ($bookImages as $image) {
            if (file_exists(public_path($image->image))) {
                unlink(public_path($image->image));
            }
            $image->delete();
        }

        // Delete the book
        $book->delete();

        return redirect()->route('book.show')->with('success', 'Book deleted successfully!');
    }


    public function deleteSelectedBooks(Request $request)
    {
        // Ensure selected_books is an array
        $selectedBooks = explode(',', $request->input('selected_books', ''));

        if (empty($selectedBooks) || count($selectedBooks) === 0) {
            return redirect()->back()->with('error', 'No books selected for deletion.');
        }

        // Delete books
        Book::whereIn('id', $selectedBooks)->delete();

        return redirect()->route('book.show')->with('success', 'Selected books deleted successfully!');
    }


}
