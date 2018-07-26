<?php

/**
 * Component creation and configuration
 *
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 *
 * @package phing
 */
class PropertyHelper
{
    /** @var Project $project */
    private static $project;
    private $next;

    /** Project properties map (usually String to String). */
    private $properties = [];

    /**
     * Map of "user" properties (as created in the Ant task, for example).
     * Note that these key/value pairs are also always put into the
     * project properties, so only the project properties need to be queried.
     * Mapping is String to String.
     */
    private $userProperties = [];

    /**
     * Map of inherited "user" properties - that are those "user"
     * properties that have been created by tasks and not been set
     * from the command line or a GUI tool.
     * Mapping is String to String.
     */
    private $inheritedProperties = [];

// --------------------  Hook management  --------------------

    /**
     * Set the project for which this helper is performing property resolution
     *
     * @param Project $p the project instance.
     */
    public function setProject(Project $p)
    {
        self::$project = $p;
    }

    /** There are 2 ways to hook into property handling:
     *  - you can replace the main PropertyHelper. The replacement is required
     * to support the same semantics (of course :-)
     *
     *  - you can chain a property helper capable of storing some properties.
     *  Again, you are required to respect the immutability semantics (at
     *  least for non-dynamic properties)
     *
     * @param PropertyHelper $next the next property helper in the chain.
     */
    public function setNext(PropertyHelper $next)
    {
        $this->next = $next;
    }

    /**
     * Get the next property helper in the chain.
     *
     * @return PropertyHelper the next property helper.
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Factory method to create a property processor.
     * Users can provide their own or replace it using "ant.PropertyHelper"
     * reference. User tasks can also add themselves to the chain, and provide
     * dynamic properties.
     *
     * @param Project $project the project fro which the property helper is required.
     *
     * @return PropertyHelper the project's property helper.
     */
    public static function getPropertyHelper(Project $project)
    {
        /** @var PropertyHelper $helper */
        $helper = $project->getReference('phing.PropertyHelper');
        if ($helper !== null) {
            return $helper;
        }
        $helper = new self();
        $helper->setProject($project);

        $project->addReference('phing.PropertyHelper', $helper);
        return $helper;
    }

    // --------------------  Methods to override  --------------------

    /**
     * Sets a property. Any existing property of the same name
     * is overwritten, unless it is a user property. Will be called
     * from setProperty().
     *
     * If all helpers return false, the property will be saved in
     * the default properties table by setProperty.
     *
     * @param string $ns   The namespace that the property is in (currently
     *             not used.
     * @param string $name The name of property to set.
     *             Must not be <code>null</code>.
     * @param string $value The new value of the property.
     *              Must not be <code>null</code>.
     * @param bool $inherited True if this property is inherited (an [sub]ant[call] property).
     * @param bool $user      True if this property is a user property.
     * @param bool $isNew     True is this is a new property.
     * @return bool true if this helper has stored the property, false if it
     *    couldn't. Each helper should delegate to the next one (unless it
     *    has a good reason not to).
     */
    public function setPropertyHook($ns, $name, $value, $inherited, $user, $isNew)
    {
        if ($this->getNext() !== null) {
            $subst = $this->getNext()->setPropertyHook($ns, $name, $value, $inherited, $user, $isNew);
            // If next has handled the property
            if ($subst) {
                return true;
            }
        }

        return false;
    }

    /** Get a property. If all hooks return null, the default
     * tables will be used.
     *
     * @param string $ns namespace of the sought property.
     * @param string $name name of the sought property.
     * @param bool $user True if this is a user property.
     * @return string The property, if returned by a hook, or null if none.
     */
    public function getPropertyHook($ns, $name, $user)
    {
        if ($this->getNext() !== null) {
            $o = $this->getNext()->getPropertyHook($ns, $name, $user);
            if ($o !== null) {
                return $o;
            }
        }

        if (self::$project !== null && StringHelper::startsWith('toString:', $name)) {
            $name = StringHelper::substring($name, strlen('toString:'));
            $v = self::$project->getReference($name);
            return ($v === null) ? null : (string) $v;
        }

        return null;
    }

    // -------------------- Optional methods   --------------------
    // You can override those methods if you want to optimize or
    // do advanced things (like support a special syntax).
    // The methods do not chain - you should use them when embedding ant
    // (by replacing the main helper)

    /**
     * Replaces <code>${xxx}</code> style constructions in the given value
     * with the string value of the corresponding data types.
     *
     * @param string $ns    The namespace for the property.
     * @param string $value The string to be scanned for property references.
     *              May be <code>null</code>, in which case this
     *              method returns immediately with no effect.
     * @param string[] $keys  Mapping (String to String) of property names to their
     *              values. If <code>null</code>, only project properties will
     *              be used.
     *
     * @exception BuildException if the string contains an opening
     *                           <code>${</code> without a closing
     *                           <code>}</code>
     * @return string the original string with the properties replaced, or
     *         <code>null</code> if the original string is <code>null</code>.
     */
    public function replaceProperties($ns, $value, $keys)
    {
        if ($value === null) {
            return null;
        }
        if ($keys === null) {
            $keys = self::$project->getProperties();
        }
        // Because we're not doing anything special (like multiple passes),
        // regex is the simplest / fastest.  PropertyTask, though, uses
        // the old parsePropertyString() method, since it has more stringent
        // requirements.

        $sb = $value;
        $iteration = 0;
        // loop to recursively replace tokens
        while (strpos($sb, '${') !== false) {
            $sb = preg_replace_callback(
                '/\$\{([^\$}]+)\}/',
                function ($matches) use ($keys) {
                    $propertyName = $matches[1];

                    $replacement = null;
                    if (array_key_exists($propertyName, $keys)) {
                        $replacement = $keys[$propertyName];
                    }

                    if ($replacement === null) {
                        $replacement = $this->getProperty(null, $propertyName);
                    }

                    if ($replacement === null) {
                        self::$project->log(
                            'Property ${' . $propertyName . '} has not been set.',
                            Project::MSG_VERBOSE
                        );

                        return $matches[0];
                    }

                    self::$project->log(
                        'Property ${' . $propertyName . '} => ' . (string) $replacement,
                        Project::MSG_VERBOSE
                    );

                    return $replacement;
                },
                $sb
            );

            // keep track of iterations so we can break out of otherwise infinite loops.
            $iteration++;
            if ($iteration === 5) {
                return $sb;
            }
        }

        return $sb;
    }

    // -------------------- Default implementation  --------------------
    // Methods used to support the default behavior and provide backward
    // compatibility. Some will be deprecated, you should avoid calling them.


    /** Default implementation of setProperty. Will be called from Project.
     *  This is the original 1.5 implementation, with calls to the hook
     *  added.
     * @param string $ns      The namespace for the property (currently not used).
     * @param string $name    The name of the property.
     * @param string $value   The value to set the property to.
     * @param bool $verbose If this is true output extra log messages.
     * @return bool true if the property is set.
     */
    public function setProperty($ns, $name, $value, $verbose)
    {
        // user (CLI) properties take precedence
        if (isset($this->userProperties[$name])) {
            if ($verbose) {
                self::$project->log('Override ignored for user property ' . $name, Project::MSG_VERBOSE);
            }
            return false;
        }

        $done = $this->setPropertyHook($ns, $name, $value, false, false, false);
        if ($done) {
            return true;
        }

        if ($verbose && isset($this->properties[$name])) {
            self::$project->log('Overriding previous definition of property ' . $name,
                Project::MSG_VERBOSE);
        }

        if ($verbose) {
            self::$project->log('Setting project property: ' . $name . " -> "
                . $value, Project::MSG_DEBUG);
        }
        $this->properties[$name] = $value;
        self::$project->addReference($name, new PropertyValue($value));
        return true;
    }

    /**
     * Sets a property if no value currently exists. If the property
     * exists already, a message is logged and the method returns with
     * no other effect.
     *
     * @param string $ns   The namespace for the property (currently not used).
     * @param string $name The name of property to set.
     *             Must not be <code>null</code>.
     * @param string $value The new value of the property.
     *              Must not be <code>null</code>.
     */
    public function setNewProperty($ns, $name, $value)
    {
        if (isset($this->properties[$name])) {
            self::$project->log('Override ignored for property ' . $name, Project::MSG_VERBOSE);
            return;
        }

        $done = $this->setPropertyHook($ns, $name, $value, false, false, true);
        if ($done) {
            return;
        }

        self::$project->log('Setting project property: ' . $name . " -> " . $value, Project::MSG_DEBUG);
        if ($name !== null && $value !== null) {
            $this->properties[$name] = $value;
            self::$project->addReference($name, new PropertyValue($value));
        }
    }

    /**
     * Sets a user property, which cannot be overwritten by
     * set/unset property calls. Any previous value is overwritten.
     * @param string $ns   The namespace for the property (currently not used).
     * @param string $name The name of property to set.
     *             Must not be <code>null</code>.
     * @param string $value The new value of the property.
     *              Must not be <code>null</code>.
     */
    public function setUserProperty($ns, $name, $value)
    {
        if ($name === null || $value === null) {
            return;
        }
        self::$project->log('Setting ro project property: ' . $name . ' -> ' . $value, Project::MSG_DEBUG);
        $this->userProperties[$name] = $value;

        $done = $this->setPropertyHook($ns, $name, $value, false, true, false);
        if ($done) {
            return;
        }
        $this->properties[$name] = $value;
        self::$project->addReference($name, new PropertyValue($value));
    }

    /**
     * Sets an inherited user property, which cannot be overwritten by set/unset
     * property calls. Any previous value is overwritten. Also marks
     * these properties as properties that have not come from the
     * command line.
     *
     * @param string $ns   The namespace for the property (currently not used).
     * @param string $name The name of property to set.
     *             Must not be <code>null</code>.
     * @param string $value The new value of the property.
     *              Must not be <code>null</code>.
     */
    public function setInheritedProperty($ns, $name, $value)
    {
        if ($name === null || $value === null) {
            return;
        }
        $this->inheritedProperties[$name] = $value;

        self::$project->log("Setting ro project property: " . $name . " -> "
            . $value, Project::MSG_DEBUG);
        $this->userProperties[$name] = $value;

        $done = $this->setPropertyHook($ns, $name, $value, true, false, false);
        if ($done) {
            return;
        }
        $this->properties[$name] = $value;
        self::$project->addReference($name, new PropertyValue($value));
    }

    // -------------------- Getting properties  --------------------

    /**
     * Returns the value of a property, if it is set.  You can override
     * this method in order to plug your own storage.
     *
     * @param string $ns   The namespace for the property (currently not used).
     * @param string $name The name of the property.
     *             May be <code>null</code>, in which case
     *             the return value is also <code>null</code>.
     * @return string the property value, or <code>null</code> for no match
     *         or if a <code>null</code> name is provided.
     */
    public function getProperty($ns, $name)
    {
        if ($name === null) {
            return null;
        }
        $o = $this->getPropertyHook($ns, $name, false);
        if ($o !== null) {
            return $o;
        }

        $found = $this->properties[$name] ?? null;
        // check to see if there are unresolved property references
        if (false !== strpos($found, '${')) {
            // attempt to resolve properties
            $found = $this->replaceProperties(null, $found, null);
            // save resolved value
            $this->properties[$name] = $found;
        }

        return $found;
    }

    /**
     * Returns the value of a user property, if it is set.
     *
     * @param string $ns The namespace for the property (currently not used).
     * @param string $name The name of the property.
     *             May be <code>null</code>, in which case
     *             the return value is also <code>null</code>.
     * @return string the property value, or <code>null</code> for no match
     *         or if a <code>null</code> name is provided.
     */
    public function getUserProperty($ns, $name)
    {
        if ($name === null) {
            return null;
        }
        $o = $this->getPropertyHook($ns, $name, true);
        if ($o !== null) {
            return $o;
        }
        return $this->userProperties[$name] ?? null;
    }


    // -------------------- Access to property tables  --------------------
    // This is used to support ant call and similar tasks. It should be
    // deprecated, it is possible to use a better (more efficient)
    // mechanism to preserve the context.

    /**
     * Returns a copy of the properties table.
     * @return array a hashtable containing all properties
     *         (including user properties).
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Returns a copy of the user property hashtable
     * @return array a hashtable containing just the user properties
     */
    public function getUserProperties()
    {
        return $this->userProperties;
    }

    /**
     * Copies all user properties that have not been set on the
     * command line or a GUI tool from this instance to the Project
     * instance given as the argument.
     *
     * <p>To copy all "user" properties, you will also have to call
     * {@link #copyUserProperties copyUserProperties}.</p>
     *
     * @param Project $other the project to copy the properties to.  Must not be null.
     */
    public function copyInheritedProperties(Project $other)
    {
        foreach ($this->inheritedProperties as $arg => $value) {
            if ($other->getUserProperty($arg) !== null) {
                continue;
            }
            $value = $this->inheritedProperties[$arg];
            $other->setInheritedProperty($arg, (string) $value);
        }
    }

    /**
     * Copies all user properties that have been set on the command
     * line or a GUI tool from this instance to the Project instance
     * given as the argument.
     *
     * <p>To copy all "user" properties, you will also have to call
     * {@link #copyInheritedProperties copyInheritedProperties}.</p>
     *
     * @param Project $other the project to copy the properties to.  Must not be null.
     */
    public function copyUserProperties(Project $other)
    {
        foreach ($this->userProperties as $arg => $value) {
            if (isset($this->inheritedProperties[$arg])) {
                continue;
            }
            $other->setUserProperty($arg, $value);
        }
    }

    /**
     * Parses a string containing <code>${xxx}</code> style property
     * references into two lists. The first list is a collection
     * of text fragments, while the other is a set of string property names.
     * <code>null</code> entries in the first list indicate a property
     * reference from the second list.
     *
     * It can be overridden with a more efficient or customized version.
     *
     * @param string $value     Text to parse. Must not be <code>null</code>.
     * @param array $fragments List to add text fragments to.
     *                  Must not be <code>null</code>.
     * @param array $propertyRefs List to add property names to.
     *                     Must not be <code>null</code>.
     *
     * @throws BuildException if the string contains an opening
     *                           <code>${</code> without a closing
     *                           <code>}</code>
     */
    public function parsePropertyString($value, &$fragments, &$propertyRefs)
    {
        $prev = 0;
        $pos = 0;

        while (($pos = strpos($value, '$', $prev)) !== false) {
            if ($pos > $prev) {
                $fragments[] = StringHelper::substring($value, $prev, $pos - 1);
            }
            if ($pos === (strlen($value) - 1)) {
                $fragments[] = '$';
                $prev = $pos + 1;
            } elseif ($value{$pos + 1} !== '{') {

                // the string positions were changed to value-1 to correct
                // a fatal error coming from function substring()
                $fragments[] = StringHelper::substring($value, $pos, $pos + 1);
                $prev = $pos + 2;
            } else {
                $endName = strpos($value, '}', $pos);
                if ($endName === false) {
                    throw new BuildException("Syntax error in property: $value");
                }
                $propertyName = StringHelper::substring($value, $pos + 2, $endName - 1);
                $fragments[] = null;
                $propertyRefs[] = $propertyName;
                $prev = $endName + 1;
            }
        }

        if ($prev < strlen($value)) {
            $fragments[] = StringHelper::substring($value, $prev);
        }
    }
}
