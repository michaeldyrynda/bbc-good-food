<?php

namespace App\Console\Commands;

use App\Chef;
use App\IngredientSection;
use App\Metadata;
use App\Recipe;
use App\Show;
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
        $this->filterRecipes($this->fetchSitemap())
            ->reject(function ($recipeUrl) {
                return Recipe::where('source_url', $recipeUrl)->exists();
            })->each(function ($recipeUrl) {
                try {
                    $recipeData = $this->parseRecipeData($recipeUrl);

                    $recipe = Recipe::create([
                        'name'        => $recipeData['name'],
                        'image_url'   => $recipeData['image'],
                        'description' => $recipeData['description'],
                        'fingerprint' => $recipeData['fingerprint'],
                        'source_url'  => $recipeData['source_url'],
                    ]);

                    if (! is_null($recipeData['show'])) {
                        $show = Show::firstOrCreate([ 'name' => $recipeData['show'], ]);
                        $recipe->show()->attach($show->id);
                    }

                    if (! is_null($recipeData['chef'])) {
                        $chef = Chef::firstOrCreate([
                            'name'  => $recipeData['chef']['name'],
                            'image' => $recipeData['chef']['image'],
                        ]);
                        $recipe->chef()->attach($chef->id);
                    }

                    collect($recipeData['metadata'])->each(function ($item, $key) use ($recipe) {
                        $metadata = Metadata::firstOrCreate([ 'label' => $key, ]);
                        $recipe->metadata()->attach($metadata->id, [ 'value' => $item, ]);
                    });

                    $sort = 0;

                    collect($recipeData['ingredients'])->each(function ($item, $key) use ($recipe, &$sort) {
                        $section = IngredientSection::firstOrCreate([
                            'recipe_id'  => $recipe->id,
                            'label'      => $key,
                            'sort_order' => $sort,
                        ]);
                        
                        collect($item)->each(function ($ingredient) use ($section) {
                            $section->ingredients()->create([ 'body' => $ingredient, ]);
                        });

                        $section->recipe()->associate($recipe)->save();

                        $sort++;
                    });

                    collect($recipeData['method'])->each(function ($item, $key) use ($recipe) {
                        $recipe->method()->create([ 'body' => $item, 'sort_order' => $key, ]);
                    });

                    $this->info(sprintf('[%s] Processed recipe %s', date('Y-m-d H:i:s'), $recipe->name));
                } catch (\Exception $e) {
                    $this->info(sprintf('[%s] Exception parsing data [%s]. Retrying later [%s].',
                        date('Y-m-d H:i:s'),
                        $e->getMessage(),
                        $recipeUrl
                    ));
                }
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

        $this->crawler = new Crawler;
        $this->crawler->addHtmlContent($response);

        $recipe = [
            'source_url'  => $recipeUrl,
            'name'        => $this->title(),
            'image'       => $this->image(),
            'description' => $this->description(),
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


    protected function image()
    {
        $image = $this->crawler->filter('.recipe-media .recipe-media__image');

        return $image->count() ? $image->attr('src') : null;
    }


    protected function description()
    {
        $description = $this->crawler->filter('.recipe-media .recipe-description .recipe-description__text');

        return $description->count() ? trim($description->text()) : null;
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
        $chef  = $this->crawler->filter('.recipe-chef .chef .chef__about');

        if (! $chef->count()) {
            return null;
        }

        $image = $this->crawler->filter('.recipe-chef .chef .chef__image-link .chef__image');

        return [
            'name'  => $chef->filter('.chef__name .chef__link')->first()->text(),
            'image' => $image->count() ? $image->attr('src') : null,
        ];
    }


    protected function show()
    {
        $show = $this->crawler->filter('.recipe-chef .chef .chef__about .chef__programme-name .chef__link');
        
        return $show->count() ? $show->first()->text() : null;
    }


    protected function ingredients()
    {
        $ingredients = [];

        if ($this->crawler->filter('.recipe-ingredients h3')->count()) {
            $this->crawler->filter('.recipe-ingredients h3')->each(function ($node) use (&$ingredients) {
                $ingredients[$node->text()] = $node->nextAll()
                    ->filter('.recipe-ingredients__list')
                    ->first()
                    ->filter('.recipe-ingredients__list-item')
                    ->each(function ($ingredient) {
                        return trim($ingredient->text());
                    });
            });
        } else {
            $ingredients[''] = $this->crawler->filter('.recipe-ingredients .recipe-ingredients__list-item')->each(function ($ingredient) {
                return trim($ingredient->text());
            });
        }

        return $ingredients;
    }


    protected function method()
    {
        return $this->crawler->filter('.recipe-method__list .recipe-method__list-item')->each(function ($node) {
            return trim($node->text());
        });
    }
}
