<?php
class Student
{
    protected string $name;
    protected int $course;
    protected int $identificationNumber;

    public function __construct(
        string $name = 'Не вказано',
        int $course = 1,
        int $identificationNumber = 0
    ) {
        $this->name = trim($name) !== '' ? trim($name) : 'Не вказано';
        $this->course = $course > 0 ? $course : 1;
        $this->setIdentificationNumber($identificationNumber);
    }

    public function getIdentificationNumber(): int
    {
        return $this->identificationNumber;
    }

    public function setIdentificationNumber(int $identificationNumber): void
    {
        if ($identificationNumber < 0) {
            throw new InvalidArgumentException('Ідентифікаційний номер не може бути від’ємним.');
        }

        $this->identificationNumber = $identificationNumber;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function display(): void
    {
        echo '<section class="student-card">';
        echo '<h2>Студент</h2>';
        echo '<p><strong>Ім’я:</strong> ' . $this->escape($this->name) . '</p>';
        echo '<p><strong>Курс:</strong> ' . $this->course . '</p>';
        echo '<p><strong>Ідентифікаційний номер:</strong> ' . $this->identificationNumber . '</p>';
        echo '</section>';
    }

    public function __destruct()
    {
        echo '<p class="destructor">Деструктор: об’єкт класу ' . $this->escape(get_class($this)) . ' завершив роботу.</p>';
    }
}

class GraduateStudent extends Student
{
    private string $thesisTopic;

    public function __construct(
        string $name = 'Не вказано',
        int $course = 1,
        int $identificationNumber = 0,
        string $thesisTopic = 'Тема не вказана'
    ) {
        parent::__construct($name, $course, $identificationNumber);
        $this->setThesisTopic($thesisTopic);
    }

    public function setThesisTopic(string $thesisTopic): void
    {
        $thesisTopic = trim($thesisTopic);
        $this->thesisTopic = $thesisTopic !== '' ? $thesisTopic : 'Тема не вказана';
    }

    public function display(): void
    {
        echo '<section class="student-card graduate-card">';
        echo '<h2>Студент-дипломник</h2>';
        echo '<p><strong>Ім’я:</strong> ' . $this->escape($this->name) . '</p>';
        echo '<p><strong>Курс:</strong> ' . $this->course . '</p>';
        echo '<p><strong>Ідентифікаційний номер:</strong> ' . $this->identificationNumber . '</p>';
        echo '<p><strong>Тема дипломної роботи:</strong> ' . $this->escape($this->thesisTopic) . '</p>';
        echo '</section>';
    }
}

$studentDefault = new Student();
$studentExample = new Student('Андрій Коваленко', 2, 1001);
$graduateDefault = new GraduateStudent();
$graduateStudent = new GraduateStudent(
    'Олена Шевченко',
    4,
    2001,
    'Розробка вебзастосунку для обліку студентських проєктів'
);

$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $rawNumber = $_POST['identification_number'] ?? '';
    $newNumber = filter_var($rawNumber, FILTER_VALIDATE_INT);
    $newTopic = trim($_POST['thesis_topic'] ?? '');
    $messages = [];

    if ($newNumber === false || $newNumber === null || $newNumber < 0) {
        $messages[] = 'Введіть цілий невід’ємний ідентифікаційний номер.';
        $messageType = 'error';
    } else {
        $graduateStudent->setIdentificationNumber($newNumber);
        $messages[] = 'Ідентифікаційний номер оновлено.';
    }

    if ($newTopic === '') {
        $messages[] = 'Тема дипломної роботи не може бути порожньою.';
        $messageType = 'error';
    } else {
        $graduateStudent->setThesisTopic($newTopic);
        $messages[] = 'Тему дипломної роботи оновлено.';
    }

    $message = implode(' ', $messages);
}

function html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Класи Student і GraduateStudent</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f5f8; color: #202733; margin: 0; padding: 28px 16px; }
        main { max-width: 900px; margin: 0 auto; }
        h1 { margin-bottom: 8px; }
        .intro { color: #576273; margin-bottom: 24px; }
        .student-card, form { background: white; border: 1px solid #dfe4eb; border-radius: 12px; padding: 18px 22px; margin: 14px 0; box-shadow: 0 2px 8px rgba(20, 30, 50, .04); }
        .student-card h2 { margin-top: 0; font-size: 20px; }
        .graduate-card { border-left: 5px solid #5476a8; }
        label { display: block; font-weight: bold; margin: 14px 0 6px; }
        input { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #bdc7d5; border-radius: 7px; font-size: 16px; }
        button { background: #244d80; color: white; border: 0; border-radius: 7px; padding: 11px 16px; margin-top: 16px; font-size: 16px; cursor: pointer; }
        .message { padding: 12px 14px; border-radius: 8px; background: #e6f4e8; margin: 15px 0; }
        .message.error { background: #fde8e7; }
        .note, .destructor { color: #657083; font-size: 13px; }
    </style>
</head>
<body>
<main>
    <h1>Класи «Студент» і «Студент-дипломник»</h1>

    <?php if ($message !== ''): ?>
        <div class="message <?= $messageType === 'error' ? 'error' : '' ?>"><?= html($message) ?></div>
    <?php endif; ?>

    <?php $studentDefault->display(); ?>
    <?php $studentExample->display(); ?>
    <?php $graduateDefault->display(); ?>
    <?php $graduateStudent->display(); ?>

    <form method="post" action="">
        <h2>Зміна даних студента-дипломника</h2>
        <label for="identification_number">Новий ідентифікаційний номер</label>
        <input type="number" id="identification_number" name="identification_number" min="0" step="1" value="<?= $graduateStudent->getIdentificationNumber() ?>" required>
        <label for="thesis_topic">Нова тема дипломної роботи</label>
        <input type="text" id="thesis_topic" name="thesis_topic" maxlength="200" value="" placeholder="Введіть нову тему" required>
        <button type="submit">Змінити дані</button>
    </form>
    <p class="note">Зміни демонструються під час поточного запиту. Без бази даних значення не зберігаються після повторного відкриття сторінки.</p>

    <?php
    unset($studentDefault, $studentExample, $graduateDefault, $graduateStudent);
    ?>
</main>
</body>
</html>
