<?php

return [
    'show_custom_fields' => false, // Show custom fields in the edit profile form
    'custom_fields' => [
        'live_dashboard' => [
            'type' => 'text',
            'label' => 'Live dashboard URL',
            'placeholder' => 'https://live.parking****.com/admin',
            'required' => false,
            'rules' => 'required|string|max:255',
        ],
        'live_parking' => [
            'type' => 'text',
            'label' => 'Parking app URL',
            'placeholder' => 'https://pay.easypark****.com',
            'required' => false,
            'rules' => 'required|string|max:255',
        ],
        // 'custom_field_1' => [
        //     'type' => 'text',
        //     'label' => 'Custom Textfield 1',
        //     'placeholder' => 'Custom Field 1',
        //     'required' => true,
        //     'rules' => 'required|string|max:255',
        // ],
        // 'custom_field_2' => [
        //     'type' => 'select',
        //     'label' => 'Custom Select 2',
        //     'placeholder' => 'Select',
        //     'required' => true,
        //     'options' => [
        //         'option_1' => 'Option 1',
        //         'option_2' => 'Option 2',
        //         'option_3' => 'Option 3',
        //     ],
        // ],
        // 'custom_field_3' => [
        //     'type' =>'textarea',
        //     'label' => 'Custom Textarea 3',
        //     'placeholder' => 'Textarea',
        //     'rows' => '3',
        //     'required' => true,
        // ],
        // 'custom_field_4' => [
        //     'type' => 'datetime',
        //     'label' => 'Custom Datetime 4',
        //     'placeholder' => 'Datetime',
        //     'seconds' => false,
        // ],
        // 'custom_field_5' => [
        //     'type' => 'boolean',
        //     'label' => 'Custom Boolean 5',
        //     'placeholder' => 'Boolean'
        // ],
    ]
];
