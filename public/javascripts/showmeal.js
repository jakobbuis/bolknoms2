$(document).on('ready', function(){
    // Print action
    $('#print').on('click', print_list);

    // Add a new registration
    $('#new_registration').on('submit', add_registration);

    // Remove registration
    $('#registrations').on('click', '.remove_registration', remove_registration);
});

function print_list()
{
    // Ask for confirmation if there are no names on the list
    var counter = parseInt($('#count').html());
    if (counter == 0 && !confirm('De lijst is leeg. Weet je zeker dat je deze wilt afdrukken?')) {
        return;
    }
    window.print();
}

/**
 * Add a new registration
 * @param {Event} event
 */
function add_registration(event)
{
    event.preventDefault();

    var form = this;

    // Send AJAX-call to register for meal
    $.ajax({
        type: 'POST',
        url: '/administratie/aanmelden',
        contentType: 'application/json',
        dataType: 'html',
        data: JSON.stringify({
            meal_id:  $(this).attr('data-meal_id'),
            name:     $('#name').val(),
            handicap: $('#handicap').val()
        }),
        success: function(response) {
            $('#registrations').append(response);
            update_counter(+1);
            form.reset();
        },
        error: fatal_error
    });
}

/**
 * Remove a registration from this meal
 * @param  {Event} event
 */
function remove_registration(event)
{
    event.preventDefault();

    // Ask for confirmation
    var registration = $(this).parents('.registration');
    if (confirm('Weet je zeker dat je '+$(this).attr('data-name')+' wilt uitschrijven?')) {

        $.ajax({
            type: 'POST',
            url: '/administratie/afmelden/'+$(this).attr('data-id'),
            contentType: 'application/json',
            dataType: 'html',
            success: function(response) {
                registration.remove();
                update_counter(-1);
            },
            error: fatal_error
        });
    }
}

/**
 * Updates the number of registrations in the interface
 * @param  {int} increment the number to add to the counter, can be negative
 */
function update_counter(increment)
{
    var counter = $('#count');
    var value = parseInt(counter.html());
    value += increment;
    counter.html(value);
}
