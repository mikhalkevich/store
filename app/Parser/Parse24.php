<?php

namespace App\Parser;

use Symfony\Component\DomCrawler\Crawler;
use App\Catalog;
use App\Products;
use Auth;

class Parse24 implements ParseContract
{
    use ParseTrait;
    public $crawler;
    private $obj_id;
    private $cat_id;
    public function __construct()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');
    }

    public function getParse()
    {
        $ff = 'https://24shop.by/catalog/';
        $file = file_get_contents($ff);
        $this->crawler = new Crawler($file);
        //$tt = $this->html($this->crawler, '.images_table');
        $this->crawler->filter('.section')->each(function (Crawler $node, $i) {
            $name = $this->text($node, "h3");
            $body = $this->text($node, ".esc-lead-snippet-wrapper");
            $picture = $this->attr($node, ".esc-thumbnail-image", "src");
            $this->obj_id = $this->updateCatalog($name, 0, '/catalog/');
            $node->filter('.list-block li')->each(function (Crawler $node2, $i2) {
                $name2 = $this->text($node2, "a span");
                $url = $this->attr($node2, "a", "href");
                $obj2 = $this->updateCatalog($name2, $this->obj_id, $url);

            });
            sleep(1);
            echo "<hr />";
        });
    }

    public function postAll()
    {
        $all = Catalog::where('type', 'https://24shop.by')->get();
        foreach ($all as $one) {
            $this->updateProducts($one->id);
        }
    }

    public function updateProducts($catalog_id)
    {
        $cat = Catalog::find($catalog_id);
        $this->cat_id = $cat->id;
        if ($cat->url != null) {
            $ff = 'https://24shop.by' . $cat->url;
            $file = file_get_contents($ff);
            $this->crawler = new Crawler($file);
            $this->crawler->filter('.catalog_table__item')->each(function (Crawler $node, $i) {
                $prod_name = $this->text($node, ".name a span");
                $prod_price = $this->html($node, ".price");
                $prod_description = $this->html($node, ".description");
                $prod_picture = $this->attr($node, '.image_link img', 'src');
                $prod_obj = Products::where('name', $prod_name)->first();
                if (!$prod_obj) {
                    $prod_new = new Products;
                    $prod_new->name = $prod_name;
                    $prod_new->document = 'https://24shop.by';
                    $prod_new->picture = $prod_picture;
                    $prod_new->price = $prod_price;
                    $prod_new->vip = 0;
                    $prod_new->characters = $prod_description;
                    $prod_new->categories_id = $this->cat_id;
                    $prod_new->manufactor_id = null;
                    $prod_new->user_id = (Auth::guest())?0:Auth::user()->id;
                    $prod_new->save();
                }
            });
        }
    }

    public function updateCatalog($name = null, $parent_id = 0, $url = null)
    {
        $obj = Catalog::where('name', $name)->first();
        $obj_in = new Catalog;
        if (!$obj) {
            $obj_in->name = $name;
            $obj_in->parent_id = $parent_id;
            $obj_in->body = '';
            $obj_in->picture = '';
            $obj_in->type = 'https://24shop.by';
            $obj_in->url = $url;
            $obj_in->save();

        } else {

        }
        return $obj_in->id;
    }
}