<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FaqController extends Controller
{
    public function index()
    {
        $faq = Faq::all();
        return response()->json(['data' => $faq]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'answer' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq = Faq::create($request->all());
        return response()->json(['data' => $faq], 201);
    }

    public function show(Faq $faq)
    {
        return response()->json(['data' => $faq]);
    }

    public function update(Request $request, Faq $faq)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'answer' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq->update($request->all());
        return response()->json(['data' => $faq]);
    }

    public function getAnswer(Request $request)
    {
        $question = $request->input('q', '');

        // Pecah jadi kata-kata
        $keywords = explode(' ', strtolower($question));

        // Cari semua FAQ yang mengandung kata kunci
        $faqs = Faq::where(function($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('question', 'like', "%{$word}%");
                }
                })->get();

        // Gabungkan jawaban
        if ($faqs->count() > 0) {
            $answer = $faqs->pluck('answer')->implode("\n\n");
            } else {
            $answer = "Maaf, saya belum punya jawaban untuk pertanyaan itu.";
        }

        return response()->json(['answer' => $answer]);

    }


    public function destroy(Faq $faq)
    {
        $faq->delete();
        return response()->json(null, 204);
    }
}