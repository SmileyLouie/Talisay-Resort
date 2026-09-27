<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPermission extends Model
{
    use HasFactory;

    protected $table = 'staff_permissions';

    protected $fillable = [
        'user_id',
        'module',
        'actions',
    ];

    protected $casts = [
        'actions' => 'array',
    ];

    /**
     * Standard list of supported system modules and their available action capabilities.
     */
    public const MODULES = [
        'bookings' => [
            'name'        => 'Booking Management',
            'description' => 'Guest reservations, booking calendar, check-in, check-out, and status updates.',
            'icon'        => 'bi-calendar-check-fill',
            'actions'     => ['view', 'create', 'edit', 'confirm', 'cancel', 'check_in_out', 'delete'],
            'default'     => ['view', 'create', 'edit', 'confirm', 'check_in_out'],
        ],
        'payments' => [
            'name'        => 'Payment Management',
            'description' => 'Payment records, GCash proof verification, approval, rejection, and receipts.',
            'icon'        => 'bi-credit-card-fill',
            'actions'     => ['view', 'verify', 'update', 'refund'],
            'default'     => ['view', 'verify'],
        ],
        'accommodations' => [
            'name'        => 'Room & Cottage Management',
            'description' => 'Room and cottage units, pricing, amenities, and availability status.',
            'icon'        => 'bi-building',
            'actions'     => ['view', 'create', 'edit', 'toggle_availability', 'delete'],
            'default'     => ['view', 'edit', 'toggle_availability'],
        ],
        'housekeeping' => [
            'name'        => 'Housekeeping Operations',
            'description' => 'Room cleaning status, housekeeping assignments, inspection logs, and room turnover.',
            'icon'        => 'bi-stars',
            'actions'     => ['view', 'update_status', 'cleaning_request'],
            'default'     => ['view', 'update_status'],
        ],
        'maintenance' => [
            'name'        => 'Maintenance & Repairs',
            'description' => 'Facility repair tickets, equipment servicing, groundskeeping, and safety repairs.',
            'icon'        => 'bi-tools',
            'actions'     => ['view', 'create_request', 'update_status'],
            'default'     => ['view', 'create_request', 'update_status'],
        ],

        'guests' => [
            'name'        => 'Guest & Customer Management',
            'description' => 'Guest directory, booking histories, contact info, and special preferences.',
            'icon'        => 'bi-person-badge',
            'actions'     => ['view', 'edit'],
            'default'     => ['view'],
        ],
        'reviews' => [
            'name'        => 'Reviews & Feedback',
            'description' => 'Guest reviews moderation, comment blocking, and sentiment management.',
            'icon'        => 'bi-star-fill',
            'actions'     => ['view', 'approve', 'reject', 'block_comment'],
            'default'     => ['view', 'approve', 'reject'],
        ],
        'tour' => [
            'name'        => '360° Virtual Tour & Activities',
            'description' => 'Virtual tour assets, hot-spots, interactive media, and resort panoramas.',
            'icon'        => 'bi-camera-video-fill',
            'actions'     => ['view', 'manage'],
            'default'     => ['view', 'manage'],
        ],
        'reports' => [
            'name'        => 'Reports & Analytics',
            'description' => 'Operational summaries, occupancy figures, guest numbers, and PDF exports.',
            'icon'        => 'bi-bar-chart-fill',
            'actions'     => ['view', 'export'],
            'default'     => ['view', 'export'],
        ],
        'chatbot' => [
            'name'        => 'AI Chatbot & Knowledge Base',
            'description' => 'AI concierge settings, FAQs, knowledge intents, and visitor chat logs.',
            'icon'        => 'bi-chat-dots-fill',
            'actions'     => ['view', 'manage'],
            'default'     => ['view'],
        ],
        'emergency' => [
            'name'        => 'Emergency & Safety Management',
            'description' => 'Resort safety broadcasts, weather advisories, and emergency alerts.',
            'icon'        => 'bi-shield-exclamation',
            'actions'     => ['view', 'broadcast'],
            'default'     => ['view', 'broadcast'],
        ],
    ];

    /**
     * Common Role Presets for convenient 1-click assignment.
     */
    public const PRESETS = [
        'front_desk' => [
            'name'        => 'Front Desk / Booking Staff',
            'department'  => 'Front Office',
            'position'    => 'Front Desk Officer',
            'description' => 'Full booking workflow, reservations, guest check-in/out, and room availability.',
            'modules'     => [
                'bookings'       => ['view', 'create', 'edit', 'confirm', 'check_in_out'],
                'accommodations' => ['view', 'toggle_availability'],
                'guests'         => ['view'],
                'reviews'        => ['view'],
            ],
        ],
        'cashier' => [
            'name'        => 'Cashier / Payment Staff',
            'department'  => 'Finance & Billing',
            'position'    => 'Billing & Cashier Specialist',
            'description' => 'Verification and processing of GCash & cash payments, and booking lookup.',
            'modules'     => [
                'payments' => ['view', 'verify', 'update'],
                'bookings' => ['view'],
            ],
        ],
        'housekeeper' => [
            'name'        => 'Housekeeping Staff',
            'department'  => 'Housekeeping',
            'position'    => 'Housekeeping Attendant',
            'description' => 'Assigned cleaning tasks, room turnover tracking, and cleaning status.',
            'modules'     => [
                'housekeeping'   => ['view', 'update_status'],
                'accommodations' => ['view'],
            ],
        ],
        'maintenance' => [
            'name'        => 'Maintenance Technician',
            'department'  => 'Maintenance & Operations',
            'position'    => 'Facility Maintenance Technician',
            'description' => 'Facility repairs, work orders, preventive maintenance, and task completion.',
            'modules'     => [
                'maintenance' => ['view', 'create_request', 'update_status'],
            ],
        ],
        'guest_relations' => [
            'name'        => 'Guest Relations & Concierge',
            'department'  => 'Guest Services',
            'position'    => 'Guest Relations Specialist',
            'description' => 'Reviews moderation, guest requests, 360 tour assistance, and chatbot overview.',
            'modules'     => [
                'reviews'  => ['view', 'approve', 'reject'],
                'tour'     => ['view'],
                'guests'   => ['view'],
            ],
        ],
        'operations_lead' => [
            'name'        => 'Resort Operations Supervisor',
            'department'  => 'Resort Operations',
            'position'    => 'Operations Supervisor',
            'description' => 'Broad operational access spanning bookings, rooms, housekeeping, and tasks.',
            'modules'     => [
                'bookings'       => ['view', 'create', 'edit', 'confirm', 'check_in_out'],
                'accommodations' => ['view', 'edit', 'toggle_availability'],
                'housekeeping'   => ['view', 'update_status', 'cleaning_request'],
                'maintenance'    => ['view', 'create_request', 'update_status'],
                'payments'       => ['view'],
                'reviews'        => ['view', 'approve'],
            ],
        ],
    ];

    /**
     * Permission belongs to a User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
