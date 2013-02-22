# Montage Controllers

## How does a request get turned into a Controller?

Montage sees urls like:

    http://app.com/foo/bar/baz/che

as a pattern that looks like this:

    http://app.com/ControllerClass/Method/$baz/$che

So, in a default Montage installation, the request string: `/foo/bar/baz/che` would be converted to:

    \Namespace\FooEndpoint::handleBar($baz, $che);

Notice that `foo` gets converted to `FooEndpoint` and the method gets converted from `bar` to `handleBar`. This is by convention, class names will always be appended with `Endpoint` or `Command` and method names will always start with `handle`.

## What about default controllers and methods?

Default controller is `\Namespace\DefaultEndpoint` and default method is: `handleDefault`. That means, the above example could resolve many different ways:

    \Namespace\FooEndpoint::handleBar($baz, $che);
    \Namespace\FooEndpoint::handleDefault($bar, $baz, $che);

    \Namespace\DefaultEndpoint::handleFoo($bar, $baz, $che);
    \Namespace\DefaultEndpoint::handleDefault($foo, $bar, $baz, $che);

Basically, Montage checks to see what is valid starting from most verbose to least verbose (ie, foo class, bar method to default class with default method).

## What about catch alls or extra url path bits we do not care about?

Say we have a request like this:

    http://app.com/blog/1234/this-is-the-title

And we do not really care about `this-is-the-title`, so:

    http://app.com/blog/1234/

would be just as valid. Then you can set your controller like this:

    \Controller\BlogController::handleIndex($id, $title = '');

By setting a default value for `$title`, we are telling Montage not to worry about it if it is not there, but since `$id` does not have a default, it must be there or a 404 Exception will be thrown.

What if there are a variable number of values we do not care about?

    http://app.com/blog/1234/this-is-the-title/val1/val2/val3

So, any of these would be valid:

    http://app.com/blog/1234/this-is-the-title/val1/val2/val3
    http://app.com/blog/1234/this-is-the-title/val1/
    http://app.com/blog/1234/this-is-the-title
    http://app.com/blog/1234/

This will handle it:

    \Controller\BlogController::handleIndex($id, array $params = array());

By declaring the variable after $id to be an array, we are telling Montage to just put everything after `$id` in the `$params` array.

## What about Get and Post variables?

Say you had the url:

    http://app.com/blog/1234/?foo=bar&baz=che

You can get access to `foo` and `baz` through the request object:

    echo $this->request->getField('foo'); // bar

## Command line request?

Montage has a handy CLI controller `\Montage\Controller\Command` with some handy CLI specific methods to make command line scripts easy to create.

So, if we wanted to create a foo script:

    class FooCommand extends \Montage\Controller\Command {

      public function handleDefault(){}//method

      public function handleBar(){}//method
    
    }//class

Invoking a command follows this general outline:

    > php path/to/montage.php controller/method [--var1=val1 ...][--var2=val2 ...][-var3 val3...]

So, to call the `Foo` command, we could call that script by using the following command in the shell:

    > php path/to/montage.php foo

to call the `handleDefault` method, and:

    > php path/to/montage.php foo/bar

to call the `handleBar` method.

