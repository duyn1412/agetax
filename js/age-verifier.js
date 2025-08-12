// JavaScript code to handle the form submission
jQuery(document).ready(function($) {

    // Listen for change on the billing and shipping province select fields
    $('form.checkout').on('change', '#billing_state, #shipping_state', function() {
        // Trigger update event on checkout form
        $('form.checkout').trigger('update');
    });

    
    $('#d-age-verification-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        console.log("Form submitted");
        var day = $('#day').val();
        var month = $('#month').val();
        var year = $('#year').val();
        var province = $('#province').val();

        // Verify the age
        if (!verifyAge(day, month, year)) {
            $('#error-message').show();
            return false;
        } else {
            $('#error-message').hide();
        }

        // Use AJAX to update province session
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_province_session',
                province: province,
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Province updated successfully, reload page
                    location.reload();
                } else {
                    console.error('Failed to update province:', response.data);
                    // Fallback to form submission
                    $('#d-age-verification-form')[0].submit();
                }
            },
            error: function() {
                console.error('AJAX request failed');
                // Fallback to form submission
                $('#d-age-verification-form')[0].submit();
            }
        });
    });
});

// Verify the age
function verifyAge(day, month, year) {
    var birthDate = new Date(year, month - 1, day);
    var ageDifMs = Date.now() - birthDate.getTime();
    var ageDate = new Date(ageDifMs);
    return Math.abs(ageDate.getUTCFullYear() - 1970) > 18;
}