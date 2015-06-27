<h1>Aftekenlijst <?= $meal ?></h1>
<p class="subtitle">
    <span id="count"><?= $meal->registrations()->confirmed()->count(); ?></span> eters
    &mdash;
    sluitingstijd <?= date('H:i', strtotime($meal->locked)); ?> uur
    &mdash;
    etenstijd <?= date('H:i', strtotime($meal->mealtime)); ?> uur
    <span class="non_print">
        &mdash;
        <a href="/administratie/<?=$meal->id;?>/edit">maaltijd bewerken</a>
    </span>
</p>

<h2>
    Eters
    <img id="print" src="/images/printer.png" alt="Print eterslijst" title="Print eterslijst" width="32" height="32">
</h2>

<p id="print_instructions">
    Bonnetjes opgehaald door: _____________________________
</p>

<ul id="registrations">
    <?php foreach ($meal->registrations()->confirmed()->get() as $r): ?>
        <?= View::make('meal/_registration', ['registration' => $r]); ?>
    <?php endforeach; ?>
</ul>

<h2>Nieuwe eter toevoegen</h2>
<form action="#new_registration" id="new_registration" data-meal_id="<?= $meal->id; ?>">
    <p>
        <label for="name">Naam</label><br>
        <input type="text" id="name" name="name">
    </p>
    <p>
        <label for="handicap">Handicap</label><br>
        <input type="text" id="handicap" name="handicap">
    </p>
    <p>
        <input type="submit" value="Toevoegen">
    </p>
</form>

<link rel="stylesheet" href="/stylesheets/print.css" media="print" />
