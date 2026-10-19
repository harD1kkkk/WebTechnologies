<?php
abstract class Figure
{
    abstract public function area(): float;
}

class Rectangle extends Figure
{
    public function __construct(private float $a, private float $b) {}

    public function area(): float
    {
        return $this->a * $this->b;
    }
}

class Circle extends Figure
{
    public function __construct(private float $r) {}

    public function area(): float
    {
        return pi() * $this->r * $this->r;
    }
}

class RightTriangle extends Figure
{
    public function __construct(private float $a, private float $b) {}

    public function area(): float
    {
        return $this->a * $this->b / 2;
    }
}

class Trapezoid extends Figure
{
    public function __construct(
        private float $a,
        private float $b,
        private float $h
    ) {}

    public function area(): float
    {
        return ($this->a + $this->b) * $this->h / 2;
    }
}

$figures = [
    'Прямокутник (сторони 5 і 3)' => new Rectangle(5, 3),
    'Коло (радіус 2)' => new Circle(2),
    'Прямокутний трикутник (катети 6 і 4)' => new RightTriangle(6, 4),
    'Трапеція (основи 3 і 7, висота 4)' => new Trapezoid(3, 7, 4),
];
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обчислення площ фігур</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 760px;
            margin: 40px auto;
            padding: 0 16px;
            color: #222;
        }
        h1 { font-size: 26px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Обчислення площ геометричних фігур</h1>

    <table>
        <tr>
            <th>Фігура</th>
            <th>Площа</th>
        </tr>
        <?php foreach ($figures as $name => $figure): ?>
            <tr>
                <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format($figure->area(), 2, '.', '') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
