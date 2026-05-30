<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocsController extends Controller
{
    public function index()
    {
        // Require authentication
        // if (!auth()->check()) {
        //     return redirect()->route('login');
        // }

        // // Parent role is not allowed to view the system documentation
        // if (auth()->user()->hasRole('parent')) {
        //     abort(403, 'غير مصرح لك بالوصول لتوثيق النظام الإداري.');
        // }

        $filePath = resource_path('docs.md');
        
        $markdownContent = '';
        if (file_exists($filePath)) {
            $markdownContent = file_get_contents($filePath);
        }

        // Convert the markdown to HTML using Laravel's built-in markdown engine (Str::markdown)
        $htmlContent = Str::markdown($markdownContent);

        return view('docs', [
            'htmlContent' => $htmlContent,
        ]);
    }
}
