jQuery(document).ready(function($) {
    var segment_index = $('.flight-segment').length;
    $('#add-segment').on('click', function() {
        var new_segment = '<div class="flight-segment"><table class="form-table">' +
            '<tr><th><label>Flight code</label></th><td><input type="text" name="segments[' + segment_index + '][flight_code]" required /></td></tr>' +
            '<tr><th><label>Airline</label></th><td><input type="text" name="segments[' + segment_index + '][airline]" /></td></tr>' +
            '<tr><th><label>Origin</label></th><td><input type="text" name="segments[' + segment_index + '][origin]" /></td></tr>' +
            '<tr><th><label>Destination</label></th><td><input type="text" name="segments[' + segment_index + '][destination]" /></td></tr>' +
            '<tr><th><label>Departure</label></th><td><input type="datetime-local" name="segments[' + segment_index + '][departure]" /></td></tr>' +
            '<tr><th><label>Arrival</label></th><td><input type="datetime-local" name="segments[' + segment_index + '][arrival]" /></td></tr>' +
            '</table><button type="button" class="button remove-segment">Remover Segmento</button><hr></div>';
        $('#flight-segments-wrapper').append(new_segment);
        segment_index++;
    });

    $('#flight-segments-wrapper').on('click', '.remove-segment', function() {
        $(this).closest('.flight-segment').remove();
    });
});