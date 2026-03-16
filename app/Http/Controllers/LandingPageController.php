<?php

namespace App\Http\Controllers;

use App\Support\SiteContent;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(): View
    {
        return view('site.home', SiteContent::get());
    }

    public function showProduct(string $slug): View
    {
        $content = SiteContent::get();

        foreach ($content['catalogSections'] as $section) {
            foreach ($section['items'] as $product) {
                if ($product['slug'] === $slug) {
                    return view('site.product', array_merge($content, [
                        'product' => $product,
                        'productSection' => $section['title'],
                    ]));
                }
            }
        }

        abort(404);
    }
}