<?php
if (isset($_FILES['foto'])) {
    echo 'Tamaño recibido: ' . $_FILES['foto']['size'] . ' bytes<br>';
    echo 'En MB: ' . round($_FILES['foto']['size'] / 1024 / 1024, 2) . ' MB<br>';
    echo 'Error code: ' . $_FILES['foto']['error'] . '<br>';
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="foto">
    <button type="submit">Subir</button>
</form>