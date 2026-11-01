<?php
// ============================================
// RESOURCE CATEGORY FUNCTIONS
// ============================================

/**
 * Get all resource categories grouped by section
 * Returns array with group names as keys and category arrays as values
 */
function getResourceCategories() {
    return [
        'Academic' => [
            'Academic Stress',
            'Study Difficulties',
            'Test Anxiety',
            'Time Management',
            'Low Motivation'
        ],
        'Emotional Wellness' => [
            'Stress Management',
            'Anxiety Management',
            'Depression Support',
            'Emotional Wellness',
            'Anger Management',
            'Self-Esteem'
        ],
        'Coping Strategies' => [
            'Coping Strategies',
            'Mindfulness',
            'Relaxation Techniques'
        ],
        'Social & Relationships' => [
            'Bullying Awareness',
            'Cyberbullying Awareness',
            'Healthy Relationships',
            'Peer Pressure',
            'Social Skills'
        ],
        'Personal Growth' => [
            'Self-Care',
            'Study Habits',
            'Time Management',
            'Personal Development',
            'Goal Setting'
        ],
        'Health & Wellness' => [
            'Sleep Hygiene',
            'Physical Wellness',
            'Nutrition & Mental Health',
            'Screen Time Balance'
        ],
        'Future Planning' => [
            'Career Guidance',
            'College Preparation',
            'Scholarship Information'
        ],
        'Special Topics' => [
            'Mental Health Awareness',
            'LGBTQ+ Support',
            'Grief Support',
            'Trauma Recovery'
        ]
    ];
}

/**
 * Render category dropdown as HTML options
 * @param string $selectedValue - Currently selected category (optional)
 */
function renderCategoryOptions($selectedValue = '') {
    $categories = getResourceCategories();
    
    foreach ($categories as $groupName => $catList) {
        echo '<optgroup label="' . htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($catList as $cat) {
            $selected = ($selectedValue === $cat) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' 
                 . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') 
                 . '</option>';
        }
        echo '</optgroup>';
    }
}

/**
 * Get flat list of all categories
 */
function getAllResourceCategoriesFlat() {
    $categories = getResourceCategories();
    $flat = [];
    foreach ($categories as $group) {
        foreach ($group as $cat) {
            $flat[] = $cat;
        }
    }
    return array_unique($flat);
}
?>