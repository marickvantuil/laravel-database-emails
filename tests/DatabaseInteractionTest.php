<?php

namespace Tests;

use Buildcode\LaravelDatabaseEmails\Email;
use Illuminate\Support\Facades\DB;

class DatabaseInteractionTest extends TestCase
{
    /** @test */
    function label_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['label' => 'welcome-email']);

        $this->assertEquals('welcome-email', DB::table('emails')->find(1)->label);
        $this->assertEquals('welcome-email', $email->getLabel());
    }

    /** @test */
    function recipient_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['recipient' => 'john@doe.com']);

        $this->assertEquals('john@doe.com', DB::table('emails')->find(1)->recipient);
        $this->assertEquals('john@doe.com', $email->getRecipient());
    }

    /** @test */
    function cc_and_bcc_should_be_saved_correctly()
    {
        $email = $this->sendEmail([
            'cc'  => $cc = [
                'john@doe.com',
            ],
            'bcc' => $bcc = [
                'jane@doe.com'
            ]
        ]);

        $this->assertEquals(json_encode($cc), DB::table('emails')->find(1)->cc);
        $this->assertTrue($email->hasCc());
        $this->assertEquals(['john@doe.com'], $email->getCc());
        $this->assertEquals(json_encode($bcc), DB::table('emails')->find(1)->bcc);
        $this->assertTrue($email->hasBcc());
        $this->assertEquals(['jane@doe.com'], $email->getBcc());
    }

    /** @test */
    function subject_should_be_saved_correclty()
    {
        $email = $this->sendEmail(['subject' => 'test subject']);

        $this->assertEquals('test subject', DB::table('emails')->find(1)->subject);
        $this->assertEquals('test subject', $email->getSubject());
    }

    /** @test */
    function view_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['view' => 'tests::dummy']);

        $this->assertEquals('tests::dummy', DB::table('emails')->find(1)->view);
        $this->assertEquals('tests::dummy', $email->getView());
    }

    /** @test */
    function encrypted_should_be_saved_correctly()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, DB::table('emails')->find(1)->encrypted);
        $this->assertFalse($email->isEncrypted());

        $this->app['config']['laravel-database-emails.encrypt'] = true;

        $email = $this->sendEmail();

        $this->assertEquals(1, DB::table('emails')->find(2)->encrypted);
        $this->assertTrue($email->isEncrypted());
    }

    /** @test */
    function scheduled_date_should_be_saved_correctly()
    {
        $email = $this->sendEmail();
        $this->assertNull(DB::table('emails')->find(1)->scheduled_at);
        $this->assertNull($email->getScheduledDate());

        $email = $this->scheduleEmail('+2 weeks');
        $this->assertNotNull(DB::table('emails')->find(2)->scheduled_at);
        $this->assertEquals(date('Y-m-d H:i:s', strtotime('+2 weeks')), $email->getScheduledDate());
    }

    /** @test */
    function the_body_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'Jane Doe']]);

        $expectedBody = "Name: Jane Doe\n";

        $this->assertSame($expectedBody, DB::table('emails')->find(1)->body);
        $this->assertSame($expectedBody, $email->getBody());
    }

    /** @test */
    function variables_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'John Doe']]);

        $this->assertEquals(json_encode(['name' => 'John Doe'], 1), DB::table('emails')->find(1)->variables);
        $this->assertEquals(['name' => 'John Doe'], $email->getVariables());
    }

    /** @test */
    function the_sent_date_should_be_null()
    {
        $email = $this->sendEmail();

        $this->assertNull(DB::table('emails')->find(1)->sent_at);
        $this->assertNull($email->getSendDate());
    }

    /** @test */
    function failed_should_be_zero()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, DB::table('emails')->find(1)->failed);
        $this->assertFalse($email->hasFailed());
    }

    /** @test */
    function attempts_should_be_zero()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, DB::table('emails')->find(1)->attempts);
        $this->assertEquals(0, $email->getAttempts());
    }

    /** @test */
    function the_scheduled_date_should_be_saved_correctly()
    {
        $scheduledFor = date('Y-m-d H:i:s', strtotime('+2 weeks'));

        $email = $this->scheduleEmail('+2 weeks');

        $this->assertTrue($email->isScheduled());
        $this->assertEquals($scheduledFor, $email->getScheduledDate());
    }

    /** @test */
    function recipient_should_be_swapped_for_test_address_when_in_testing_mode()
    {
        $this->app['config']->set('laravel-database-emails.testing.enabled', function () {
            return true;
        });
        $this->app['config']->set('laravel-database-emails.testing.email', 'test@address.com');

        $email = $this->sendEmail(['recipient' => 'jane@doe.com']);

        $this->assertEquals('test@address.com', $email->getRecipient());
    }
}