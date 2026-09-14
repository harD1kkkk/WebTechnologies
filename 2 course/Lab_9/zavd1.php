<?php

class TemplateEngine {

    public function render($templateFile, $data) {
        $template = file_get_contents($templateFile);

        // 1. include іншого шаблону
        $template = preg_replace_callback('/{{\s*include\s+[\'"](.*?)[\'"]\s*}}/', function($matches) {
            return file_get_contents($matches[1]);
        }, $template);

        // 2. if блоки
        $template = preg_replace_callback('/{{if (.*?)}}(.*?){{endif}}/s', function($matches) use ($data) {
            $variable = trim($matches[1]);

            if (!empty($data[$variable])) {
                return $matches[2];
            }

            return '';
        }, $template);

        // 3. foreach блоки
        $template = preg_replace_callback('/{{foreach (.*?)}}(.*?){{endforeach}}/s', function($matches) use ($data) {
            $arrayName = trim($matches[1]);
            $block = $matches[2];
            $result = '';

            if (isset($data[$arrayName]) && is_array($data[$arrayName])) {
                foreach ($data[$arrayName] as $item) {
                    $tempBlock = $block;

                    if (is_array($item)) {
                        foreach ($item as $key => $value) {
                            $tempBlock = str_replace("{{item.$key}}", $value, $tempBlock);
                        }
                    } else {
                        $tempBlock = str_replace("{{item}}", $item, $tempBlock);
                    }

                    $result .= $tempBlock;
                }
            }

            return $result;
        }, $template);

        // 4. змінні
        $template = preg_replace_callback('/{{(.*?)}}/', function($matches) use ($data) {
            $key = trim($matches[1]);

            // object.property
            if (strpos($key, '.') !== false) {
                $parts = explode('.', $key);

                if (count($parts) == 2) {
                    $object = $parts[0];
                    $property = $parts[1];

                    if (isset($data[$object][$property])) {
                        return $data[$object][$property];
                    }
                }
            }

            return $data[$key] ?? '';
        }, $template);

        return $template;
    }
}

$data = [
    "title" => "Мій сайт",
    "isLoggedIn" => true,

    "student" => [
        "name" => "Іван",
        "group" => "КН-21"
    ],

    "products" => [
        ["name" => "Ноутбук", "price" => 25000],
        ["name" => "Мишка", "price" => 800],
        ["name" => "Клавіатура", "price" => 1500]
    ]
];

$engine = new TemplateEngine();

echo $engine->render("template.html", $data);

?>
