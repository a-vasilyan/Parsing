<?php
class CurlRequest {
    public function get($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
}

class HtmlParser {
    private $dom;

    public function __construct($html) {
        $this->dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $this->dom->loadHTML($html);
        libxml_clear_errors();
    }

    public function getElementsByClass($className) {
        return $this->dom->getElementsByTagName($className);
    }

    public function getElementAttributeValue($element, $attribute) {
        return $element->getAttribute($attribute);
    }

    public function getElementTextContent($element) {
        return $element->textContent;
    }
}

class SimpleHtmlParser {
    private $curlRequest;

    public function __construct(CurlRequest $curlRequest) {
        $this->curlRequest = $curlRequest;
    }

    public function parsePage($url) {
        $result = [];
        $html = $this->curlRequest->get($url);

        if ($html) {
            $htmlParser = new HtmlParser($html);
            $menuItems = $htmlParser->getElementsByClass('a');

            foreach ($menuItems as $menuItem) {
                $classNames = $htmlParser->getElementAttributeValue($menuItem, 'class');
                if (strpos($classNames, 'item food-full-view') !== false) {
                    $dish = new Dish();
                    $dish->name = $htmlParser->getElementTextContent($menuItem->getElementsByTagName('span')->item(1));
                    $dish->price = $htmlParser->getElementTextContent($menuItem->getElementsByTagName('span')->item(2));
                    $dish->price = preg_replace('/[^\d]/', ' ', $dish->price) . '₽';
                    $dish->image_url = $htmlParser->getElementAttributeValue($menuItem->getElementsByTagName('span')->item(0), 'style');
                    $dish->description = $htmlParser->getElementTextContent($menuItem->getElementsByTagName('span')->item(6));
                    $result[] = $dish;
                }
            }
        }

        return $result;
    }
}

class Dish {
    public $name;
    public $price;
    public $image_url;
    public $description;
}


$url = 'https://flyfoods.ru/';
$curlRequest = new CurlRequest();
$parser = new SimpleHtmlParser($curlRequest);
$result = $parser->parsePage($url);


echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>