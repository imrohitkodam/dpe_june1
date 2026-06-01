function RSTicketsProSelectUser(self) {
    if (window.parent) {
        var func = 'jSelectUser_' + document.getElementsByName('field')[0].value;
        var alt_email;
        if (typeof window.parent[func] == 'function') {
            var id = self.getAttribute('data-user-value');
            var name = self.getAttribute('data-user-name');
            alt_email =  self.getAttribute('data-alt-email');
            window.parent[func](id, name);
        }
        if (typeof window.parent.jSelectUser == 'function') {
            alt_email = self.getAttribute('data-alt-email');
            window.parent.jSelectUser(self);
        }

        if (typeof alt_email !== 'undefined') {
            var alt_email_field = window.parent.document.getElementById('jform_alternative_email');
            if (alt_email_field != null) {
                alt_email_field.value = alt_email;
            }
            var alt_email_field_ticket = window.parent.document.getElementById('ticket_alternative_email');
            if (alt_email_field_ticket != null) {
                alt_email_field_ticket.value = alt_email;
            }
        }
    }
}