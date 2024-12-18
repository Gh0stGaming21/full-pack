<?php
class ResourceSharingController {

    // Fetch resources based on filters
    public function getResources($filters = []) {
        // Get the database connection from the Database class
        $db = (new Database())->connect();

        // Start building the query
        $query = "SELECT * FROM resources WHERE availability = 'available'";

        // Dynamic filters
        foreach ($filters as $key => $value) {
            $query .= " AND $key = :$key";
        }

        // Pagination (optional)
        if (isset($filters['limit'])) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        try {
            $stmt = $db->prepare($query);

            // Bind parameters dynamically
            foreach ($filters as $key => $value) {
                if ($key === 'limit' || $key === 'offset') {
                    $stmt->bindValue(":$key", (int)$value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
                }
            }

            // Execute the query
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return []; // Return empty array on failure
        }
    }

    // Create a new resource
    public function createResource($user_id, $name, $category, $description, $availability) {
        // Get the database connection from the Database class
        $db = (new Database())->connect();

        // Prepare and execute the SQL query to insert a resource
        $stmt = $db->prepare("
            INSERT INTO resources (user_id, name, category, description, availability, created_at)
            VALUES (:user_id, :name, :category, :description, :availability, NOW())
        ");

        $result = $stmt->execute([
            ':user_id' => $user_id,
            ':name' => $name,
            ':category' => $category,
            ':description' => $description,
            ':availability' => $availability
        ]);

        if ($result) {
            error_log("Resource successfully added: " . $name);
        } else {
            error_log("Failed to add resource: " . $stmt->errorInfo()[2]);
        }

        return $result;
    }
}
?>
