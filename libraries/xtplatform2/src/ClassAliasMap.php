<?php

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Psr\Log\Test\DummyTest;
use Psr\Log\Test\LoggerInterfaceTest;
use Psr\Log\Test\TestLogger;

// PSR standards are not prefixed any more
@class_alias(MessageInterface::class, 'XTP_BUILD\Psr\Http\Message\MessageInterface');
@class_alias(RequestInterface::class, 'XTP_BUILD\Psr\Http\Message\RequestInterface');
@class_alias(ResponseInterface::class, 'XTP_BUILD\Psr\Http\Message\ResponseInterface');
@class_alias(ServerRequestInterface::class, 'XTP_BUILD\Psr\Http\Message\ServerRequestInterface');
@class_alias(StreamInterface::class, 'XTP_BUILD\Psr\Http\Message\StreamInterface');
@class_alias(UploadedFileInterface::class, 'XTP_BUILD\Psr\Http\Message\UploadedFileInterface');
@class_alias(UriInterface::class, 'XTP_BUILD\Psr\Http\Message\UriInterface');

// PSR standards are not prefixed any more
@class_alias(AbstractLogger::class, 'XTP_BUILD\Psr\Log\AbstractLogger');
@class_alias(InvalidArgumentException::class, 'XTP_BUILD\Psr\Log\InvalidArgumentException');
@class_alias(LoggerAwareInterface::class, 'XTP_BUILD\Psr\Log\LoggerAwareInterface');
@class_alias(LoggerAwareTrait::class, 'XTP_BUILD\Psr\Log\LoggerAwareTrait');
@class_alias(LoggerInterface::class, 'XTP_BUILD\Psr\Log\LoggerInterface');
@class_alias(LoggerTrait::class, 'XTP_BUILD\Psr\Log\LoggerTrait');
@class_alias(LogLevel::class, 'XTP_BUILD\Psr\Log\LogLevel');
@class_alias(NullLogger::class, 'XTP_BUILD\Psr\Log\NullLogger');
// class_alias(DummyTest::class, 'XTP_BUILD\Psr\Log\Test\DummyTest');
// class_alias(LoggerInterfaceTest::class, 'XTP_BUILD\Psr\Log\Test\LoggerInterfaceTest');
// class_alias(TestLogger::class, 'XTP_BUILD\Psr\Log\Test\TestLogger');
