<?php
class Exercise {
    private $mydb;

    public function __construct() {
        global $mydb;
        $this->mydb = $mydb;
    }

    public function getAllExercises() {
        $sql = "SELECT e.*, l.LessonTitle, c.CourseName FROM tblexercise e 
                LEFT JOIN tbllesson l ON e.LessonID = l.LessonID 
                LEFT JOIN tblcourse c ON l.CourseID = c.CourseID 
                ORDER BY c.CourseName, l.LessonTitle";
        $this->mydb->setQuery($sql);
        return $this->mydb->loadResultList();
    }

    public function getExercise($id) {
        $sql = "SELECT e.*, l.LessonTitle, c.CourseName FROM tblexercise e 
                LEFT JOIN tbllesson l ON e.LessonID = l.LessonID 
                LEFT JOIN tblcourse c ON l.CourseID = c.CourseID 
                WHERE e.ExerciseID = ?";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$id]);
        return $this->mydb->get_result($stmt)->fetch_object();
    }

    public function getExercisesByLesson($lessonId) {
        $sql = "SELECT * FROM tblexercise WHERE LessonID = ? ORDER BY Question";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$lessonId]);
        return $this->mydb->get_result($stmt);
    }

    public function createExercise($data) {
        $sql = "INSERT INTO tblexercise (Question, ChoiceA, ChoiceB, ChoiceC, ChoiceD, Answer, LessonID) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['Question'],
            $data['ChoiceA'],
            $data['ChoiceB'],
            $data['ChoiceC'],
            $data['ChoiceD'],
            $data['Answer'],
            $data['LessonID']
        ]);
    }

    public function updateExercise($id, $data) {
        $sql = "UPDATE tblexercise SET Question = ?, ChoiceA = ?, ChoiceB = ?, ChoiceC = ?, 
                ChoiceD = ?, Answer = ?, LessonID = ? WHERE ExerciseID = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['Question'],
            $data['ChoiceA'],
            $data['ChoiceB'],
            $data['ChoiceC'],
            $data['ChoiceD'],
            $data['Answer'],
            $data['LessonID'],
            $id
        ]);
    }

    public function deleteExercise($id) {
        $sql = "DELETE FROM tblexercise WHERE ExerciseID = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$id]);
    }
}
?>






