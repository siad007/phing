<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/tasks/system/condition/ConditionBase.php';

/**
 * Condition that tests the OS type.
 *
 * @author    Andreas Aderhold <andi@binarycloud.com>
 * @copyright 2001,2002 THYRELL. All rights reserved
 * @version   $Id$
 * @package   phing.tasks.system.condition
 */
class OsCondition implements Condition
{
    private $family;

    /**
     * @param $f
     */
    public function setFamily($f)
    {
        $this->family = strtolower($f);
    }

    public function evaluate(): bool
    {
        return self::isOS($this->family);
    }

    /**
     * Determines if the OS on which Ant is executing matches the
     * given OS family.
     * @param string $family the family to check for
     * @return true if the OS matches
     */
    public static function isFamily($family): bool
    {
        return self::isOS($family);
    }

    /**
     * @param string $family
     * @return bool
     */
    public static function isOS($family): bool
    {
        $osName = strtolower(Phing::getProperty("os.name"));

        if ($family !== null) {
            if ($family === "windows") {
                return StringHelper::startsWith("win", $osName);
            }

            if ($family === "mac") {
                return (strpos($osName, "mac") !== false || strpos($osName, "darwin") !== false);
            }

            if ($family === ("unix")) {
                return (
                    StringHelper::endsWith("ix", $osName) ||
                    StringHelper::endsWith("ux", $osName) ||
                    StringHelper::endsWith("bsd", $osName) ||
                    StringHelper::startsWith("sunos", $osName) ||
                    StringHelper::startsWith("darwin", $osName)
                );
            }
            throw new BuildException("Don't know how to detect os family '" . $family . "'");
        }

        return false;
    }
}
