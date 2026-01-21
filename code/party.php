<?php

// Handle frontline skill updates
if(isset($_GET['update']) && $_GET['update'] == 'frontline_skills') {
    if(isset($_POST['skill_gem_1']) && isset($_POST['skill_gem_2'])) {
        $Character->Data['party_json']['members']['frontline']['skills'][0] = $_POST['skill_gem_1'];
        $Character->Data['party_json']['members']['frontline']['skills'][1] = $_POST['skill_gem_2'];
    }
}

// Handle backline skill updates
if(isset($_GET['update']) && $_GET['update'] == 'backline_skills') {
    if(isset($_POST['skill_gem_1']) && isset($_POST['skill_gem_2'])) {
        $Character->Data['party_json']['members']['backline']['skills'][0] = $_POST['skill_gem_1'];
        $Character->Data['party_json']['members']['backline']['skills'][1] = $_POST['skill_gem_2'];
    }
}
