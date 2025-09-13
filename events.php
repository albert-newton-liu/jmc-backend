<?php
class Events
{
    private $pdo;

    /**
     * Constructor to initialize the class with a PDO database connection object.
     * @param PDO $pdo The PDO object for database operations.
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Creates a new event record in the database.
     * @param array $data An associative array containing event details.
     * @return bool True on success, false on failure.
     */
    public function createEvent($data)
    {
        $sql = "INSERT INTO jmc_event (title, poster_url, date, time, location) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $data['title'],
            $data['posterUrl'],
            $data['date'],
            $data['time'],
            $data['location']
        ]);
        return $result;
    }

    /**
     * Retrieves all events from the database, ordered by ID in descending order.
     * @return array An array of event records.
     */
    public function getEvents()
    {
        $sql = "SELECT id, title, poster_url, date, time, location FROM jmc_event order by id desc";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates an existing event record.
     * @param int $id The ID of the event to update.
     * @param array $data An associative array containing the new event details.
     * @return bool True on success, false on failure.
     */
    public function updateEvent($id, $data)
    {
        $sql = "UPDATE jmc_event SET title=?, poster_url=?, date=?, time=?, location=? WHERE id=?";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $data['title'],
            $data['posterUrl'],
            $data['date'],
            $data['time'],
            $data['location'],
            $id
        ]);
        return $result;
    }

    /**
     * Deletes an event record from the database.
     * @param int $id The ID of the event to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteEvent($id)
    {
        $sql = "DELETE FROM jmc_event WHERE id=?";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([$id]);
        return $result;
    }
}
