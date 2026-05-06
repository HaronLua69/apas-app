<?php

test('registration routes are not available', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
