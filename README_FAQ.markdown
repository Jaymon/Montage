# Montage FAQ (Frequently Asked Questions)

## I love that `Montage\Start\Start::handle()` is magic and resolves passed in dependencies, but I find it annoying when I override the parent's `handle()` method I then have to update all the children, are there better ways to handle inherited dependencies?

This problem is kind of subtle, so let's start with an example:

The global App start class will have two dependencies passed in through the `handle()` method:

    /**
     *  Global App start class
     */
    namespace Start;
    
    use Montage\Start\Start as MontageStart;
    
    // these are the dependencies...
    use Foo\Bar;
    user Baz\Che;
    
    class Start extends MontageStart {
    
      public function handle(Bar $bar,Che $che){
      
        // does something important with $bar and $che here
      
      }//method
    
    }//class
    
Now, you have a _dev_ environment start class that doesn't need the `Foo\Bar` or `Baz\Che` dependencies, but has to declare them anyway to pass to the parent (the global App start class):

    /**
     *  Dev environment start class
     */
    namespace Start;
    
    use Start\Start as AppStart;
    
    // these are the dependencies for AppStart but not needed in this class...
    use Foo\Bar;
    user Baz\Che;
    
    // dependency this class needs...
    use Foobar\Brown;
    
    class DevStart extends AppStart {
    
      public function handle(Brown $brown,Bar $bar,Che $che){
      
        parent::handle($bar,$che);
      
      }//method
    
    }//class

Now, lets say you add a third dependency to the global App start class:

    public function handle(Bar $bar,Che $che,Green $green){ /* ... */ }//method
    
The problem is you will then have to go through all the child `Start` classes (in this case, `DevStart`) and add the `Green` dependency, that's annoying, and while there is no perfect way to solve it, there are some ways to mitigate the annoyance.

### 1 - You can pass the dependencies in the global App Start constructor:

    public function __construct(Bar $bar,Che $che,Green $green){
    
      $this->bar = $bar;
      $this->che = $che;
      $this->green = $green;
    
    }//method
    
    public function handle(){
      
      // use things like $this->bar to access Foo\Bar instance
    
    }//method

But that has the same problem if your child class `DevStart` also needs to use the `__construct()` method.

### 2 - Use set methods:

    public function setBar(Bar $bar){ $this->bar = $bar; }//method
    public function setChe(Che $che){ $this->che = $che; }//method
    public function setGreen(Green $green){ $this->green = $green; }//method
  
This allows you to free up the `handle()` and `__construct()` methods while still allowing you to have dependencies. The only problem is, dependencies using the set*(ClassName $var_name) syntax are treated as optional, so if the DI Container cannot resolve the dependency it will just ignore it.