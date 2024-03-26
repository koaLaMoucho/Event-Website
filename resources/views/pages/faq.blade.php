@extends('layouts.app')  

@section('content')
    <div class="container">
        <h1>Frequently Asked Questions (FAQs)</h1>

        <section class="row faqs-grid">
            @foreach ($faqs as $faq)
                <div class="col-xs-6 faq-dropdown">
                    <div class="faq-title">
                        <i class="fa-solid fa-circle-question" aria-label="Question" ></i>
                        <h3 class="faq-question">{{ $faq->question }}</h3>
                    </div>
                    <p>{{ $faq->answer }}</p>
                </div>
            @endforeach
        </section>
    </div>
@endsection

