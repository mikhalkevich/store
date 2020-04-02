<?php

namespace App\Parser;

use Symfony\Component\DomCrawler\Crawler;
use App\Products;
use App\Image;
use Auth;

class ParseGoogle implements ParseContract
{
    use ParseTrait;
    public $crawler;
    private $obj;
    public function __construct()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');
    }


    public function getParse($id = null)
    {
        $this->obj = Products::find($id);
        //dd($this->obj->name);
        $str = str_replace(' ', '+', $this->obj->name);;
        $ff = 'http://www.google.com/search?q='.$str.'&safe=active&client=ubuntu&hs=Bnu&channel=fs&source=lnms&tbm=isch&sa=X&ved=0ahUKEwjbnN2a1szeAhWECywKHRfdCKMQ_AUIDigB&biw=1366&bih=620';
        $file = file_get_contents($ff);
        $this->crawler = new Crawler($file);
        //$tt = $this->html($this->crawler, '.images_table');

        $this->crawler->filter('#search a')->each(function (Crawler $node, $i) {
            $picture = $this->attr($node, "img", "src");
            $pic = Image::where('picture', $picture)->first();
            if(!$pic){
                $pic2 = new Image;
                $pic2->name = $this->obj->name ." - " . $i;
                $pic2->product_id = $this->obj->id;
                $pic2->type = 'google';
                $pic2->small_body = 'Picture from google';
                $pic2->picture = $picture;
                $pic2->save();
            }
        });
        return true;
    }
}