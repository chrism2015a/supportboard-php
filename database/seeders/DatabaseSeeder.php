<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Category;
use App\Models\Article;
use App\Models\Setting;
use App\Models\ChatbotConfig;
use App\Models\ChatbotQnA;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@supportboard.local',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create Agent User
        User::create([
            'name' => 'Agent Smith',
            'email' => 'agent@supportboard.local',
            'password' => Hash::make('agent123'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        // Create Regular User
        User::create([
            'name' => 'John Customer',
            'email' => 'user@example.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Create Departments
        Department::create([
            'name' => 'Technical Support',
            'slug' => 'technical-support',
            'description' => 'Technical support for all products',
            'email' => 'tech@supportboard.local',
            'color' => '#3b82f6',
            'display_order' => 1,
        ]);

        Department::create([
            'name' => 'Sales',
            'slug' => 'sales',
            'description' => 'Sales and billing inquiries',
            'email' => 'sales@supportboard.local',
            'color' => '#10b981',
            'display_order' => 2,
        ]);

        Department::create([
            'name' => 'General',
            'slug' => 'general',
            'description' => 'General inquiries and feedback',
            'email' => 'info@supportboard.local',
            'color' => '#6b7280',
            'display_order' => 3,
        ]);

        // Create Knowledge Base Categories
        Category::create([
            'name' => 'Getting Started',
            'slug' => 'getting-started',
            'description' => 'Basic setup and onboarding',
            'display_order' => 1,
        ]);

        Category::create([
            'name' => 'FAQ',
            'slug' => 'faq',
            'description' => 'Frequently asked questions',
            'display_order' => 2,
        ]);

        Category::create([
            'name' => 'Billing',
            'slug' => 'billing',
            'description' => 'Billing and payment information',
            'display_order' => 3,
        ]);

        // Create Knowledge Base Articles
        Article::create([
            'title' => 'How to Create Your First Ticket',
            'content' => '<p>Follow these steps to create your first support ticket:</p><ol><li>Navigate to the Tickets page</li><li>Click "Create New Ticket"</li><li>Fill in the subject and description</li><li>Select a category and priority</li><li>Submit the ticket</li></ol>',
            'slug' => 'create-first-ticket',
            'category_id' => 1,
            'author_id' => 1,
            'status' => 'published',
            'views' => 150,
            'language' => 'en',
        ]);

        Article::create([
            'title' => 'Understanding Priority Levels',
            'content' => '<p>Our support system uses the following priority levels:</p><ul><li><strong>Low:</strong> General inquiries</li><li><strong>Normal:</strong> Standard support requests</li><li><strong>High:</strong> Urgent issues affecting business</li><li><strong>Urgent:</strong> Critical system outages</li></ul>',
            'slug' => 'understanding-priorities',
            'category_id' => 2,
            'author_id' => 1,
            'status' => 'published',
            'views' => 200,
            'language' => 'en',
        ]);

        // Create Default Settings
        Setting::updateOrCreate(
            ['key' => 'site_name'],
            ['value' => 'Support Board', 'category' => 'general']
        );

        Setting::updateOrCreate(
            ['key' => 'support_email'],
            ['value' => 'support@supportboard.local', 'category' => 'general']
        );

        Setting::updateOrCreate(
            ['key' => 'default_language'],
            ['value' => 'en', 'category' => 'appearance']
        );

        Setting::updateOrCreate(
            ['key' => 'timezone'],
            ['value' => 'UTC', 'category' => 'general']
        );

        // Create Chatbot Configuration
        $chatbot = ChatbotConfig::create([
            'name' => 'Support Bot',
            'is_active' => true,
            'welcome_message' => 'Hello! I\'m your AI support assistant. How can I help you today?',
            'fallback_message' => 'I\'m not sure I understand. Let me connect you with a human agent.',
            'human_takeover_on_fallback' => true,
            'auto_translate' => false,
            'speech_recognition' => false,
            'image_analysis' => false,
            'web_search' => true,
            'conversation_history' => true,
        ]);

        // Create Chatbot Q&A Pairs
        ChatbotQnA::create([
            'chatbot_id' => $chatbot->id,
            'question' => 'How do I reset my password?',
            'answer' => 'To reset your password, go to the login page and click "Forgot Password". Follow the instructions sent to your email.',
            'category' => 'account',
        ]);

        ChatbotQnA::create([
            'chatbot_id' => $chatbot->id,
            'question' => 'What are your support hours?',
            'answer' => 'Our support team is available 24/7. You can submit tickets anytime and we will respond within 24 hours.',
            'category' => 'general',
        ]);

        ChatbotQnA::create([
            'chatbot_id' => $chatbot->id,
            'question' => 'How do I contact support?',
            'answer' => 'You can contact support through our web chat, email at support@supportboard.local, or by creating a ticket in our system.',
            'category' => 'general',
        ]);

        echo "Database seeded successfully!\n";
        echo "Default users:\n";
        echo "  Admin: admin@supportboard.local / admin123\n";
        echo "  Agent: agent@supportboard.local / agent123\n";
        echo "  User: user@example.com / user123\n";
    }
}
