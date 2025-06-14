<?php

declare(strict_types=1);

namespace tests\app\models;

use app\libraries\Core;
use app\models\Config;
use app\models\Email;
use app\models\User;

class EmailTester extends \PHPUnit\Framework\TestCase {
    private string $footer = "\n\n--\nNOTE: This is an automated email notification, which is unable to receive replies.\nPlease refer to the course syllabus for contact information for your teaching staff.\nUpdate your email notification settings for this course here: http://localhost/courses/f21/csci1100/notifications/settings";
    /** @var Core */
    private $core;

    public function setUp(): void {
        $this->core = new Core();
        $config = new Config($this->core);
        $config->setCourse('csci1100');
        $config->setBaseUrl('http://localhost');
        $config->setTerm('f21');
        $user = new User($this->core, [
            'user_id' => 'test',
            'user_givenname' => 'Tester',
            'user_preferred_givenname' => 'Test',
            'user_familyname' => 'Person',
            'user_pronouns' => '',
            'display_pronouns' => false,
            'user_email' => null,
            'user_email_secondary' => '',
            'user_email_secondary_notify' => false
        ]);
        $this->core->setUser($user);
        $this->core->setConfig($config);
    }

    public function tearDown(): void {
        unset($_POST['Anon']);
    }

    public function testBasic(): void {
        $email = new Email($this->core, [
            'to_user_id' => 'person',
            'subject' => 'some email',
            'body' => 'email body',
        ]);

        $this->assertSame('person', $email->getUserId());
        $this->assertSame('[Submitty csci1100]: some email', $email->getSubject());
        $this->assertSame('email body' . $this->footer, $email->getBody());
    }

    public function testRelevantUrl(): void {
        $email = new Email($this->core, [
            'to_user_id' => 'person',
            'subject' => 'some email',
            'body' => "email body",
            'relevant_url' => 'http://example.com'
        ]);

        $this->assertSame('person', $email->getUserId());
        $this->assertSame('[Submitty csci1100]: some email', $email->getSubject());
        $this->assertSame(
            "email body\n\nClick here for more info: http://example.com" . $this->footer,
            $email->getBody()
        );
    }

    public function testAuthor(): void {
        $email = new Email($this->core, [
            'to_user_id' => 'person',
            'subject' => 'some email',
            'body' => "email body",
            'author' => true
        ]);

        $this->assertSame('person', $email->getUserId());
        $this->assertSame('[Submitty csci1100]: some email', $email->getSubject());
        $this->assertSame(
            "email body\n\nAuthor: Test P." . $this->footer,
            $email->getBody()
        );
    }

    public function testAuthorAnonymous(): void {
        $_POST['Anon'] = 'Anon';
        $email = new Email($this->core, [
            'to_user_id' => 'person',
            'subject' => 'some email',
            'body' => "email body",
            'author' => true
        ]);

        $this->assertSame('person', $email->getUserId());
        $this->assertSame('[Submitty csci1100]: some email', $email->getSubject());
        $this->assertSame(
            "email body" . $this->footer,
            $email->getBody()
        );
    }

    public function testAllDetails(): void {
        $email = new Email($this->core, [
            'to_user_id' => 'person',
            'subject' => 'some email',
            'body' => "email body",
            'relevant_url' => 'http://example.com',
            'author' => true
        ]);

        $this->assertSame('person', $email->getUserId());
        $this->assertSame('[Submitty csci1100]: some email', $email->getSubject());
        $this->assertSame(
            "email body\n\nAuthor: Test P.\nClick here for more info: http://example.com" . $this->footer,
            $email->getBody()
        );
    }

    public function testNotificationSettingsLink(): void {
        $email = new Email($this->core, [
            'to_user_id' => 'person',
            'subject' => 'some email',
            'body' => 'email body',
        ]);

        $expected_body = 'email body' . $this->footer;
        $this->assertSame($expected_body, $email->getBody());
    }

    public function testNotificationSettingsWithoutLink(): void {
        $this->core = new Core();
        $config = new Config($this->core);
        $config->setCourse(null);
        $config->setBaseUrl('http://localhost');
        $config->setTerm(null);
        $this->core->setConfig($config);

        $email_missing = new Email($this->core, [
            'to_user_id' => 'person',
            'subject' => 'some email',
            'body' => 'email body',
        ]);

        $expected_body = 'email body' . "\n\n--\nNOTE: This is an automated email notification, which is unable to receive replies.";

        $this->assertSame('[Submitty]: some email', $email_missing->getSubject());
        $this->assertSame($expected_body, $email_missing->getBody());
    }
}
