<?php

namespace App\Service;

use App\Entity\Lesson;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use App\Entity\Course;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Order;

class StripeService
{
    private string $stripeSecretKey;
    private UrlGeneratorInterface $urlGenerator;

    /**
     * Constructor.
     * 
     * @param string $stripeSecretKey Stripe API secret key for authentication.
     * @param UrlGeneratorInterface $urlGenerator Used to generate success/cancel URLs.
     */
    public function __construct(string $stripeSecretKey, UrlGeneratorInterface $urlGenerator)
    {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->urlGenerator = $urlGenerator;
        Stripe::setApiKey($this->stripeSecretKey);  // Set the API key for Stripe requests
    }

    /**
     * Creates a Stripe checkout session for purchasing a single lesson.
     * 
     * @param Lesson $lesson The lesson being purchased.
     * @param string $customerEmail The email of the user making the purchase.
     * @return Session The Stripe checkout session object.
     */
    public function createCheckoutSession(Lesson $lesson, string $customerEmail): Session
    {
        
        Stripe::setApiKey($this->stripeSecretKey);
    
        return Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => $customerEmail,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $lesson->getTitle(),
                    ],
                    'unit_amount' => (int) $lesson->getPrice() * 100,
                ],
                'quantity' => 1, 
            ]],
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('payment_success', ['lessonId' => $lesson->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('lesson_show', ['id' => $lesson->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    /**
     * Creates a Stripe checkout session for purchasing an entire course.
     * 
     * @param Course $course The course being purchased.
     * @param string $customerEmail The email of the user making the purchase.
     * @return Session The Stripe checkout session object.
     */
    public function createCheckoutSessionForCourse(Course $course, string $customerEmail): Session
    {
        Stripe::setApiKey($this->stripeSecretKey);

        $totalPrice = array_reduce($course->getLessons()->toArray(), fn($sum, $lesson) => $sum + $lesson->getPrice(), 0);

        return Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => $customerEmail,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Cursus: ' . $course->getName(),
                    ],
                    'unit_amount' => (int) $totalPrice * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('payment_success_course', ['courseId' => $course->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('theme_show', ['name' => $course->getTheme()->getName()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
}
