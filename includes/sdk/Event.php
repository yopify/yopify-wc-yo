<?php

/**
 * Class Yopify_Yo_Event
 * @package Yo
 */
class Yopify_Yo_Event
{
    /**
     * @var integer Id of the event type
     */
    public $id;

    /**
     * @var string unique_id1 (Optional, Can be used to avoid duplicate events. For eg. your order id)
     */
    public $unique_id1;

    /**
     * @var string unique_id2 (Optional, Can be used to avoid duplicate events. For eg. your item id)
     */
    public $unique_id2;

    /**
     * @var string Event type unique ID (optional|required if event_type_tag = '')
     */
    public $event_type_id = '';

    /**
     * @var string Url to redirect on the event click. Size range: 0..255 (required)
     */
    public $url = '';

    /**
     * @var string First name of the person on the event. Size range: 0..255
     */
    public $first_name = '';

    /**
     * @var string City where the event happened. Size range: 0..255
     */
    public $city = '';

    /**
     * @var string Province where the event happened. Size range: 0..255
     */
    public $province = '';

    /**
     * @var string Country where the event happened ISO-2 standard. Size range: 0..255
     */
    public $country = '';

    /**
     * @var string Title of the event. Size range: 0..255
     */
    public $title = '';

    /**
     * @var string Url of the image to be displayed. Size range: 0..255
     */
    public $image_url = '';

    /**
     * @var string Created timestamp
     */
    public $created_at;

    /**
     * @var string message template
     */
    public $message_template;

    /**
     * @var string Message (cannot be created)
     */
    private $message;

    /**
     * @var string Updated timestamp
     */
    public $updated_at;
}