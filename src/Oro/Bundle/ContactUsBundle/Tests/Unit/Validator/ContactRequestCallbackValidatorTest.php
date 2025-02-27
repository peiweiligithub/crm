<?php

namespace Oro\Bundle\ContactUsBundle\Tests\Unit\Validator;

use Oro\Bundle\ContactUsBundle\Entity\ContactRequest;
use Oro\Bundle\ContactUsBundle\Validator\ContactRequestCallbackValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ContactRequestCallbackValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider validationDataProvider
     *
     * @param mixed  $phone
     * @param mixed  $email
     * @param string $method
     * @param int    $expectedViolationCount
     */
    public function testValidationCallback($phone, $email, $method, $expectedViolationCount)
    {
        $request = new ContactRequest();
        $request->setPhone($phone);
        $request->setEmailAddress($email);
        $request->setPreferredContactMethod($method);

        $context = $this->createMock(ExecutionContextInterface::class);
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $context->expects($this->exactly($expectedViolationCount))
            ->method('buildViolation')
            ->willReturn($builder);
        $builder->expects($this->exactly($expectedViolationCount))
            ->method('atPath')
            ->willReturnSelf();
        $builder->expects($this->exactly($expectedViolationCount))
            ->method('addViolation');
        ContactRequestCallbackValidator::validate($request, $context);
    }

    public function validationDataProvider(): array
    {
        return [
            'phone only required'                 => [
                uniqid('phone'),
                null,
                ContactRequest::CONTACT_METHOD_PHONE,
                0
            ],
            'phone only required, error if empty' => [
                null,
                null,
                ContactRequest::CONTACT_METHOD_PHONE,
                1
            ],
            'email only required'                 => [
                null,
                uniqid('email'),
                ContactRequest::CONTACT_METHOD_EMAIL,
                0
            ],
            'email only required, error if empty' => [
                null,
                null,
                ContactRequest::CONTACT_METHOD_EMAIL,
                1
            ],
            'both required'                       => [
                null,
                null,
                ContactRequest::CONTACT_METHOD_BOTH,
                2
            ],
            'both required, email given only'     => [
                null,
                uniqid('email'),
                ContactRequest::CONTACT_METHOD_BOTH,
                1
            ],
            'both required, phone given only'     => [
                uniqid('phone'),
                null,
                ContactRequest::CONTACT_METHOD_BOTH,
                1
            ],
            'both required, both given'           => [
                uniqid('phone'),
                uniqid('email'),
                ContactRequest::CONTACT_METHOD_BOTH,
                0
            ],
        ];
    }
}
