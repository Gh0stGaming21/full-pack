<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Sharing Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" />
    <link rel="stylesheet" href="./public/assets/shareStyle.css">
</head>
<body>

<div class="container">

    <!-- Navigation Bar -->
    <nav>
        <div class="nav-left">
            <h1>Welcome, <?= isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : 'Guest'; ?></h1>
        </div>
        <button class="nav-toggle" onclick="toggleNav()">☰</button>
        <div class="nav-center">
            <a href="?page=dashboard"><i class="fa-solid fa-house"></i></a>
            <a href="?page=help_requests"><i class="fa-solid fa-tv"></i></a>
            <a href="?page=resource_sharing"><i class="fa-solid fa-share"></i></a>
            <a href="?page=events"><i class="fa-solid fa-calendar"></i></a>
        </div>
        <div class="nav-right">
            <a href="?page=profile"><i class="fa-solid fa-user"></i></a>
            <a href="?page=logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content">
        <h1>Resource Sharing Page</h1>
        <form method="POST" class="resource-form">
            <label for="name">Resource Name:</label>
            <input type="text" name="name" id="name" required>

            <label for="category">Category:</label>
            <input type="text" name="category" id="category">

            <label for="description">Description:</label>
            <textarea name="description" id="description"></textarea>

            <label for="availability">Availability:</label>
            <select name="availability" id="availability">
                <option value="available" selected>Available</option>
                <option value="donated">Donated</option>
            </select>

            <button type="submit">Add Resource</button>
        </form>
    
        <?php if (!empty($resources)): ?>
            <h2>Available Resources</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Availability</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                        <tr>
                            <td><?= htmlspecialchars($resource['name']) ?></td>
                            <td><?= htmlspecialchars($resource['category']) ?></td>
                            <td><?= htmlspecialchars($resource['description'] ?? 'No description provided') ?></td>
                            <td><?= htmlspecialchars($resource['availability']) ?></td>
                            <td><?= htmlspecialchars($resource['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No resources available at the moment.</p>
        <?php endif; ?>
    </div>

</div>

<script>
    function toggleNav() {
        const navCenter = document.querySelector('.nav-center');
        navCenter.classList.toggle('active');
    }
</script>

</body>
</html>