<h1>Aanmelden voor maaltijden</h1>

<?= view('register/_user_details'); ?>
<?= view('register/_registration_status_today'); ?>

<form action="#" id="register_form">
    <?php if (count($meals) == 0): ?>
        <p class="zero_case">Er zijn geen maaltijden open waarvoor je je kunt aanmelden.</p>
    <?php endif; ?>
    <div class="meals">
        <?php foreach ($meals as $meal): ?>
            <?php if (isset($user)): ?>
                <?= view('register/_meal', ['meal' => $meal, 'user' => $user]); ?>
            <?php else: ?>
                <?= view('register/_meal', ['meal' => $meal]); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</form>
