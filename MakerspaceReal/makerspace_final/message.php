<!-- message.php -->
<?php if (!empty($nachricht)): ?>
    <!-- Wir geben dem Ganzen eine CSS-Klasse für Styling -->
    <div style="border: 1px solid red; padding: 10px; color: red; background-color: #ffeeee; margin-bottom: 15px;">
        <?php echo $nachricht; ?>
    </div>
<?php endif; ?>