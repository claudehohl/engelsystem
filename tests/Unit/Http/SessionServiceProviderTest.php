<?php

namespace Engelsystem\Test\Unit\Http;

use Engelsystem\Http\Request;
use Engelsystem\Http\SessionServiceProvider;
use Engelsystem\Test\Unit\ServiceProviderTest;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface as StorageInterface;

class SessionServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Engelsystem\Http\SessionServiceProvider::register()
     * @covers \Engelsystem\Http\SessionServiceProvider::getSessionStorage()
     */
    public function testRegister()
    {
        $app = $this->getApp(['make', 'instance', 'bind', 'get']);

        $sessionStorage = $this->getMockForAbstractClass(StorageInterface::class);
        $sessionStorage2 = $this->getMockForAbstractClass(StorageInterface::class);

        $session = $this->getSessionMock();
        $request = $this->getRequestMock();

        /** @var MockObject|SessionServiceProvider $serviceProvider */
        $serviceProvider = $this->getMockBuilder(SessionServiceProvider::class)
            ->setConstructorArgs([$app])
            ->setMethods(['isCli'])
            ->getMock();
        $serviceProvider->expects($this->exactly(2))
            ->method('isCli')
            ->willReturnOnConsecutiveCalls(true, false);

        $app->expects($this->exactly(4))
            ->method('make')
            ->withConsecutive(
                [MockArraySessionStorage::class],
                [Session::class],
                [NativeSessionStorage::class, ['options' => ['cookie_httponly' => true]]],
                [Session::class]
            )
            ->willReturnOnConsecutiveCalls(
                $sessionStorage,
                $session,
                $sessionStorage2,
                $session
            );
        $app->expects($this->atLeastOnce())
            ->method('instance')
            ->withConsecutive(
                ['session.storage', $sessionStorage],
                ['session', $session]
            );

        $this->setExpects($app, 'bind', [StorageInterface::class, 'session.storage'], null, $this->atLeastOnce());
        $this->setExpects($app, 'get', ['request'], $request, $this->atLeastOnce());
        $this->setExpects($request, 'setSession', [$session], null, $this->atLeastOnce());
        $this->setExpects($session, 'start', null, null, $this->atLeastOnce());

        $serviceProvider->register();
        $serviceProvider->register();
    }

    /**
     * @covers \Engelsystem\Http\SessionServiceProvider::isCli()
     */
    public function testIsCli()
    {
        $app = $this->getApp(['make', 'instance', 'bind', 'get']);

        $sessionStorage = $this->getMockForAbstractClass(StorageInterface::class);

        $session = $this->getSessionMock();
        $request = $this->getRequestMock();

        $app->expects($this->exactly(2))
            ->method('make')
            ->withConsecutive(
                [MockArraySessionStorage::class],
                [Session::class]
            )
            ->willReturnOnConsecutiveCalls(
                $sessionStorage,
                $session
            );
        $app->expects($this->exactly(2))
            ->method('instance')
            ->withConsecutive(
                ['session.storage', $sessionStorage],
                ['session', $session]
            );

        $this->setExpects($app, 'bind', [StorageInterface::class, 'session.storage']);
        $this->setExpects($app, 'get', ['request'], $request);
        $this->setExpects($request, 'setSession', [$session]);
        $this->setExpects($session, 'start');

        $serviceProvider = new SessionServiceProvider($app);
        $serviceProvider->register();
    }

    /**
     * @return MockObject
     */
    private function getSessionMock()
    {
        $sessionStorage = $this->getMockForAbstractClass(StorageInterface::class);
        return $this->getMockBuilder(Session::class)
            ->setConstructorArgs([$sessionStorage])
            ->setMethods(['start'])
            ->getMock();
    }

    /**
     * @return MockObject
     */
    private function getRequestMock()
    {
        return $this->getMockBuilder(Request::class)
            ->setMethods(['setSession'])
            ->getMock();
    }
}
