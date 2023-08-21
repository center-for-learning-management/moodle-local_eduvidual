<?php

require_once("../../config.php");

$delete = $_REQUEST['delete'] ?? false;

echo '<pre>';
var_dump([
    'version' => '0002',
    'delete' => $delete,
]);

require_once($CFG->dirroot . '/mod/quiz/lib.php');

// $sql = "SELECT * FROM {flexquiz}";
$flexquizzes = $DB->get_records('flexquiz');

foreach ($flexquizzes as $flexquizz) {
    echo "Flex Quiz: {$flexquizz->name} (id: {$flexquizz->id}) => TODO: löschen\n";

    $quiz = $DB->get_record('quiz', ['id' => $flexquizz->parentquiz]);
    if (!$quiz) {
        echo "Basis Quiz: (id: {$flexquizz->parentquiz}) nicht gefunden\n";
        continue;
    }
    echo "Basis Quiz: {$quiz->name} (id: {$quiz->id}) => TODO: löschen\n";

    $sql = "SELECT c.*
                        FROM {flexquiz_children} c
                        INNER JOIN {flexquiz_student} fqs ON fqs.id=c.flexquiz_student_item
                    WHERE fqs.flexquiz=?
            ";
    $params = array($flexquizz->id);
    $children = $DB->get_records_sql($sql, $params);

    foreach ($children as $child) {
        $quiz = $DB->get_record('quiz', ['id' => $child->quizid]);
        if (!$quiz) {
            echo "Kind Quizzes: (id: {$child->quizid}) nicht gefunden\n";
            continue;
        }
        echo "Kind Quizzes: {$quiz->name} (id: {$quiz->id}) => TODO: löschen\n";

        if ($delete) {
            quiz_delete_instance($child->quizid);
        }
    }
    // var_dump($children);

    if ($delete) {
        quiz_delete_instance($flexquizz->parentquiz);
    }

    echo "\n";
}


// var_dump($flexquizzes);
