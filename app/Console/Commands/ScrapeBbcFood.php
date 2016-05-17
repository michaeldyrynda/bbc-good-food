<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeBbcFood extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:bbc-food';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape the BBC Food website for recipe content';

    protected $sitemap_url = 'http://bbc.co.uk/food/sitemap.xml';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->filterRecipes($this->fetchSitemap())->each(function ($recipeUrl) {
            return $this->parseRecipeData($recipeUrl);
        })->each(function ($recipe) {
            $recipe;
        });
    }


    protected function fetchSitemap()
    {
        $sitemap = file_get_contents($this->sitemap_url);

        return json_decode(json_encode(simplexml_load_string($sitemap)));
    }

    
    protected function filterRecipes($sitemap)
    {
        return collect($sitemap->url)->filter(function ($node) {
            return stripos($node->loc, '/recipes/') !== false;
        })->map(function ($node) {
            return $node->loc;
        });
    }


    protected function parseRecipeData($recipeUrl)
    {
        $response = @file_get_contents($recipeUrl);

        if (! $response) {
            // Try again later
            return false;
        }

        $this->crawler = new Crawler($response);

        $recipe = [
            'title'       => $this->title(),
            'metadata'    => $this->metadata(),
            'chef'        => $this->chef(),
            'show'        => $this->show(),
            'ingredients' => $this->ingredients(),
            'method'      => $this->method(),
        ];

        $recipe['fingerprint'] = md5(json_encode($recipe));

        return $recipe;
    }


    protected function title()
    {
        return $this->crawler->filter('.content-title__text')->text();
    }


    protected function metadata()
    {
        $metadata = [];

        $this->crawler->filter('.recipe-metadata__heading')->each(function ($node) use (&$metadata) {
            $item = $node->siblings()->filter('p[class^=recipe-metadata]')->first();

            if ($item->attr('itemprop')) {
                return $metadata[$item->attr('itemprop')] = $item->text();
            }
        });

        return $metadata;
    }


    protected function chef()
    {
        return [
            'name'  => $this->crawler->filter('.recipe-chef .chef .chef__about .chef__name .chef__link')->first()->text(),
            'image' => $this->crawler->filter('.recipe-chef .chef .chef__image-link .chef__image')->attr('src'),
        ];
    }


    protected function show()
    {
        return $this->crawler->filter('.recipe-chef .chef .chef__about .chef__programme-name .chef__link')->first()->text();
    }


    protected function ingredients()
    {
        $ingredients = [];

        $this->crawler->filter('.recipe-ingredients h3')->each(function ($node) use (&$ingredients) {
            $ingredients[$node->text()] = $node->nextAll()
                ->filter('.recipe-ingredients__list')
                ->first()
                ->filter('.recipe-ingredients__list-item')
                ->each(function ($ingredient) {
                    return trim($ingredient->text());
                });
        });

        return $ingredients;
    }


    protected function method()
    {
        return $this->crawler->filter('.recipe-method__list .recipe-method__list-item')->each(function ($node) {
            return trim($node->text());
        });
    }
}
