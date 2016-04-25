package {
  import flash.display.*;
  import flash.events.*;
  import flash.external.*;
  import flash.net.*;
  import flash.utils.*;
  import flash.system.*;

  public class URLRequestExample extends Sprite {

    public function URLRequestExample():void {
      trace('fangmm constructor..start');
      trace(Security.sandboxType);
      super();
      flash.system.Security.allowDomain('*');
      flash.system.Security.allowInsecureDomain("*");
      Security.loadPolicyFile('https://git.oschina.net/pron/pron/raw/master/crossdomain.xml');
      
      /*
      trace(flash.system.ApplicationDomain.currentDomain);
      */
      //trace(flash.utils.getQualifiedClassName(this.graphics));
      this.graphics.beginFill(0x333333);
      this.graphics.drawRect(0, 0, 550, 400);
      this.graphics.endFill();
      
      this.addEventListener(Event.ADDED_TO_STAGE, this.on_addedToStage);
      //this.addEventListener(Event.ENTER_FRAME, this.on_enterFrame);
      trace('fangmm constructor..end');
    }
    
    private function on_addedToStage(e:Event):void{
      trace('on_addedToStage');
      this.removeEventListener(Event.ADDED_TO_STAGE, this.on_addedToStage);
      
      this.test_URLStream();
    }
    /*
    private var idx:int=0;
    private function on_enterFrame(e:Event){
      trace(this.idx, 'on_enterFrame');
      this.idx++;
      
      
      //this.test_URLStream();
    }
    */
    private function test_URLStream():void{
      trace('test_URLStream');
      var Stream:URLStream = new flash.net.URLStream;
      Stream.addEventListener(Event.COMPLETE, function(e:Event){
        trace('on_URLStream_complete');

      });
      Stream.addEventListener(HTTPStatusEvent.HTTP_STATUS, function(e:HTTPStatusEvent){
        trace('on_URLStream_httpStatus');
      
      });
      Stream.addEventListener(IOErrorEvent.IO_ERROR, function(e:IOErrorEvent){
        trace('on_URLStream_ioError');
      
      });
      Stream.addEventListener(Event.OPEN, function(e:Event){
        trace('on_URLStream_open');
      
      });
      Stream.addEventListener(ProgressEvent.PROGRESS, function(e:ProgressEvent){
        trace('on_URLStream_progress');
      
      });
      Stream.addEventListener(SecurityErrorEvent.SECURITY_ERROR, function(e:SecurityErrorEvent){
        trace('on_URLStream_securityError');
        trace(e);
      });
      
      var Request:URLRequest = new URLRequest('https://git.oschina.net/pron/pron/raw/master/b2e2/images/image.jpg');
      Stream.load(Request);
      /*
      var Request:URLRequest = new URLRequest('https://git.oschina.net/pron/pron/raw/master/index.html');
      Stream.load(Request);
      */
    }
  }
}
