<?php
class Lesson {
    private $mydb;

    public function __construct() {
        global $mydb;
        $this->mydb = $mydb;
    }

    public function getAllLessons() {
        $sql = "SELECT l.*, c.CourseName FROM tbllesson l 
                LEFT JOIN tblcourse c ON l.CourseID = c.CourseID 
                ORDER BY c.CourseName, l.LessonChapter";
        $this->mydb->setQuery($sql);
        return $this->mydb->loadResultList();
    }

    public function getLesson($id) {
        $sql = "SELECT l.*, c.CourseName FROM tbllesson l 
                LEFT JOIN tblcourse c ON l.CourseID = c.CourseID 
                WHERE l.LessonID = ?";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$id]);
        return $this->mydb->get_result($stmt)->fetch_object();
    }

    public function getLessonsByCourse($courseId) {
        $sql = "SELECT * FROM tbllesson WHERE CourseID = ? ORDER BY LessonChapter";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        return $this->mydb->get_result($stmt);
    }

    public function createLesson($data) {
        $sql = "INSERT INTO tbllesson (LessonTitle, LessonChapter, LessonContent, CourseID, Category) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['LessonTitle'],
            $data['LessonChapter'],
            $data['LessonContent'],
            $data['CourseID'],
            $data['Category'] ?? 'Docs'
        ]);
    }

    public function updateLesson($id, $data) {
        $sql = "UPDATE tbllesson SET LessonTitle = ?, LessonChapter = ?, LessonContent = ?, 
                CourseID = ?, Category = ? WHERE LessonID = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['LessonTitle'],
            $data['LessonChapter'],
            $data['LessonContent'],
            $data['CourseID'],
            $data['Category'],
            $id
        ]);
    }

    public function deleteLesson($id) {
        $sql = "DELETE FROM tbllesson WHERE LessonID = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$id]);
    }
}
?>

