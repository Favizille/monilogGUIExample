<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function __construct(protected Book $book){}

    private function allBooks(){
        
        return $this->book->all();
    }

    public function showHome() {
        $books = $this->allBooks();
        $newArrivals = $this->book->where('new_arrival', true)->where('age_limit', '<', 18)->limit(6)->get();
        $today = Carbon::now()->format('l'); 
        $todayBooks = $this->book->where('day', $today)->get();

        return view('landing', [
            'books' => $books,
            'newArrivals' => $newArrivals,
            'todayBooks' => $todayBooks,
        ]);
    }

    public function showOngoingAll(){
        return view('ongoing_all',[
            'books' => $this->allBooks(),
            'mondayBooks' => $this->book->where('day', 'monday')->get(),
            'tuesdayBooks' => $this->book->where('day', 'tuesday')->get(),
            'wednesdayBooks' => $this->book->where('day', 'wednesday')->get(),
            'thursdayBooks' => $this->book->where('day', 'thursday')->get(),
            'fridayBooks' => $this->book->where('day', 'friday')->get(),
            'saturdayBooks' => $this->book->where('day', 'saturday')->get(),
            'sundayBooks' => $this->book->where('day', 'sunday')->get(),
        ]);
    }

    public function showGenre(){
        return view('genre', [
            'books' => $this->allBooks(),
        ]);
    }

    public function showSearch(){
       
        $topSearchedBooks = $this->book->orderByDesc('search_count')->limit(6) ->get();

        return view('search' , [
            'topSearches' => $topSearchedBooks
        ]);
    }

    public function searchBooks(Request $request)
    {
        $searchTerm = $request->input('query');

        $books = $this->book->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('author', 'LIKE', "%{$searchTerm}%")
                    ->get();

        return view('search', compact('books', 'searchTerm',));
    }

    public function showMyLibrary(){
        if (auth()->check()) {
            // Get books where the user has started but not completed reading
            $continueBooks = auth()->user()->readingBooks()->wherePivot('is_completed', false)->get();
        } else {
            // Handle for guests
            $bookIds = collect(session('reading_books', []))
                ->filter(fn($data) => !($data['is_completed'] ?? false)) // Only keep books not marked as completed
                ->keys()
                ->toArray();

            $continueBooks = $this->book->whereIn('id', $bookIds)->get();
        }


        return view('mylibrary', [
            'recommendedBooks' => $this->book->limit(10)->get(),
            'continueBooks' => $continueBooks,
        ]);
    }

    public function showAgeVerification(){
        return view('age_verification', [
            'books' => $this->allBooks(),
        ]);
    }

    public function showComplete(){
        $completeBooks = $this->book->where('status', 'complete')->get();

        return view('genre.complete_books', [
            'books' => $this->allBooks(),
            'completeBooks' => $completeBooks
        ]);
    }

    public function showDrama(){
        $dramaBooks = $this->book->where('category1', 'drama')->orWhere('category2', 'drama')->get();
        return view('genre.drama_books', [
            'books' => $this->allBooks(),
            'dramaBooks' => $dramaBooks 
        ]);
    }

    public function showRomance(){
        $romanceBooks = $this->book->where('category1', 'romance')->orWhere('category2', 'romance')->get();
        return view('genre.romance_books', [
            'books' => $this->allBooks(),
            'romanceBooks' => $romanceBooks,
        ]);
    }

    public function showBl(){
        $blBooks = $this->book->where('category1', 'bl')->orWhere('category2', 'bl')->get();
        return view('genre.bl_books', [
            'books' => $this->allBooks(),
            'blBooks' => $blBooks,
        ]);
    }

    public function showFantasy (){
        $fantasyBooks = $this->book->where('category1', 'fantasy')->orWhere('category2', 'fantasy')->get();
        return view('genre.fantasy', [
            'books' => $this->allBooks(),
            'fantasyBooks' => $fantasyBooks,
        ]);
    }

    public function showAction(){
        $actionBooks = $this->book->where('category1', 'action')->orWhere('category2', 'action')->get();
        return view('genre.action', [
            'books' => $this->allBooks(),
            'actionBooks' => $actionBooks,
        ]);
    }

    public function showComedy(){
        $comedyBooks = $this->book->where('category1', 'comedy')->orWhere('category2', 'comedy')->get();
        return view('genre.comedy', [
            'books' => $this->allBooks(),
            'comedyBooks' => $comedyBooks,
        ]);
    }

    public function showThriller(){
        $thrillerBooks = $this->book->where('category1', 'thriller')->orWhere('category2', 'thriller')->get();
        return view('genre.thriller', [
            'books' => $this->allBooks(),
            'thrillerBooks' => $thrillerBooks,
        ]);
    }

    public function showHorror(){
        $horrorBooks = $this->book->where('category1', 'horror')->orWhere('category2', 'horror')->get();
        return view('genre.horror', [
            'books' => $this->allBooks(),
            'horrorBooks' => $horrorBooks,
        ]);
    }

    public function showSchoolLife(){
        $schoolLifeBooks = $this->book->where('category1', 'school-life')->orWhere('category2', 'school-life')->get();
        return view('genre.school_life', [
            'books' => $this->allBooks(),
            'schoolLifeBooks' => $schoolLifeBooks,
        ]);
    }

    public function showScifi(){
        $scifiBooks = $this->book->where('category1', 'Sci-fi')->orWhere('category2', 'Sci-fi')->get();
        return view('genre.sci-fi', [
            'books' => $this->allBooks(),
            'scifiBooks' => $scifiBooks,
        ]);
    }

    public function showBooksToday(){
        $today = Carbon::now()->format('l'); 
        $todayBooks = $this->book->where('day', $today)->get();
        return view('books_today', [
            "todayBooks" => $todayBooks,
        ]);
    }

    public function showSingleOngoing(){
        return view('ongoing.ongoing_single',  [
            'books' => $this->allBooks(),
            'mondayBooks' => $this->book->where('day', 'monday')->get(),
        ]);
    }

    public function showTuesdayOngoing(){
        return view('ongoing.ongoing_tuesday', ['tuesdayBooks' => $this->book->where('day', 'tuesday')->get()]);
    }

    public function showWednesdayOngoing(){
        return view('ongoing.ongoing_wednesday', ['wednesdayBooks' => $this->book->where('day', 'wednesday')->get()]);
    }

    public function showThursdayOngoing(){
        return view('ongoing.ongoing_thursday', ['thursdayBooks' => $this->book->where('day', 'thursday')->get()]);
    }

    public function showFridayOngoing(){
        return view('ongoing.ongoing_friday', ['fridayBooks' => $this->book->where('day', 'friday')->get()]);
    }

    public function showSaturdayOngoing(){
        return view('ongoing.ongoing_saturday', ['saturdayBooks' => $this->book->where('day', 'saturday')->get()]);
    }

    public function showSundayOngoing(){
        return view('ongoing.ongoing_sunday', ['sundayBooks' => $this->book->where('day', 'sunday')->get()]);
    }

    public function showUpcoming(){
        $adultBooks = $this->book->where('age_limit', '>=', 18)->get();

        $ageLimitNewArrivals = $this->book
            ->where('new_arrival', true)
            ->whereRaw('CAST(age_limit AS UNSIGNED) >= 18')
            ->limit(6)
            ->get();


        return view('upcoming', [
            'ageLimitNewArrivals' => $ageLimitNewArrivals,
            'adultBooks' => $adultBooks,
            'books' => $this->book->limit(10)->get(),
        ]);
    }

    public function upcoming(){

        $ageLimitNewArrivals = $this->book
            ->where('new_arrival', true)
            ->whereRaw('CAST(age_limit AS UNSIGNED) >= 18')
            ->limit(6)
            ->get();


        return view('upcomingnone', [
            'ageLimitNewArrivals' => $ageLimitNewArrivals,
            'books' => $this->book->limit(10)->get(),
        ]);
    }

    
}
