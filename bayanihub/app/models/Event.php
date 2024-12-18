<?php
class EventModel {
    private $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function createEvent($title, $description, $location, $event_date, $created_by) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO events (title, description, location, event_date, created_by) VALUES (:title, :description, :location, :event_date, :created_by)"
        );
        return $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':location' => $location,
            ':event_date' => $event_date,
            ':created_by' => $created_by
        ]);
    }

    public function getAllEvents() {
        $stmt = $this->pdo->query("SELECT * FROM events ORDER BY event_date ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRSVP($event_id, $user_id) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO event_rsvps (event_id, user_id) VALUES (:event_id, :user_id)"
        );
        return $stmt->execute([':event_id' => $event_id, ':user_id' => $user_id]);
    }
}
?>
