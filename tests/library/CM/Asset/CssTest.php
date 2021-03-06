<?php

class CM_Asset_CssTest extends CMTest_TestCase {

    /** @var CM_Frontend_Render */
    private $_render;

    public function testAdd() {
        $render = new CM_Frontend_Render();
        $css = new CM_Asset_Css($render, 'font-size: 12;', '#foo');
        $css1 = <<<'EOD'
.test:visible {
	color: black;
	height:300px;
}
EOD;
        $css->add($css1, '.bar');
        $css->add('color: green;');
        $expected = <<<'EOD'
#foo {
  font-size: 12;
  color: green;
}
#foo .bar .test:visible {
  color: black;
  height: 300px;
}

EOD;
        $this->assertSame($expected, $css->get());
    }

    public function testImage() {
        $render = new CM_Frontend_Render();
        $css = new CM_Asset_Css($render, "background: image('icon/mailbox_read.png') no-repeat 66px 7px;");
        $url = $render->getUrlResource('layout', 'img/icon/mailbox_read.png');
        $expected = <<<EOD
background: url('$url') no-repeat 66px 7px;
EOD;
        $this->assertEquals($expected, $css->get());
    }

    public function testBackgroundImage() {
        $render = new CM_Frontend_Render();
        $css = new CM_Asset_Css($render, "background-image: image('icon/mailbox_read.png');");
        $url = $render->getUrlResource('layout', 'img/icon/mailbox_read.png');
        $expected = <<<EOD
background-image: url('$url');
EOD;
        $this->assertEquals($expected, $css->get());
    }

    public function testUrlFont() {
        $render = new CM_Frontend_Render();
        $css = new CM_Asset_Css($render, "src: url(urlFont('file.eot'));");
        $url = $render->getUrlStatic('/font/file.eot');
        $expected = <<<EOD
src: url('$url');
EOD;
        $this->assertEquals($expected, $css->get());
    }

    public function testMixin() {
        $render = new CM_Frontend_Render();
        $css = <<<'EOD'
.mixin() {
	font-size:5;
	border:1px solid red;
	#bar {
		color:blue;
	}
}
.foo {
	color:red;
	.mixin;
}
EOD;
        $css = new CM_Asset_Css($render, $css);
        $expected = <<<'EOD'
.foo {
  color: red;
  font-size: 5;
  border: 1px solid red;
}
.foo #bar {
  color: blue;
}

EOD;
        $this->assertEquals($expected, $css->get());
    }

    public function testLinearGradient() {
        $render = new CM_Frontend_Render();
        //horizontal
        $css = <<<'EOD'
.foo {
	.gradient(horizontal, #000000, rgba(30, 50,30, 0.4), 15%);
}
EOD;
        $expected = <<<'EOD'
.foo {
  filter: progid:DXImageTransform.Microsoft.gradient(GradientType=1,startColorstr=#ff000000,endColorstr=#661e321e);
  background-image: linear-gradient(left,#000000 15%,rgba(30,50,30,0.4) 100%);
  background-image: -moz-linear-gradient(left,#000000 15%,rgba(30,50,30,0.4) 100%);
  background-image: -webkit-linear-gradient(left,#000000 15%,rgba(30,50,30,0.4) 100%);
  background-image: -o-linear-gradient(left,#000000 15%,rgba(30,50,30,0.4) 100%);
  background-image: -ms-linear-gradient(left,#000000 15%,rgba(30,50,30,0.4) 100%);
  background-image: -webkit-gradient(linear,left top,right top,color-stop(15%,#000000),color-stop(100%,rgba(30,50,30,0.4)));
}

EOD;
        $css = new CM_Asset_Css($render, $css);
        $this->assertSame($expected, $css->get());
        //vertical
        $css = <<<'EOD'
.foo {
	.gradient(vertical, #000000, rgba(30, 50,30, 0.4));
}
EOD;
        $expected = <<<'EOD'
.foo {
  filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr=#ff000000,endColorstr=#661e321e);
  background-image: linear-gradient(top,#000000 0%,rgba(30,50,30,0.4) 100%);
  background-image: -moz-linear-gradient(top,#000000 0%,rgba(30,50,30,0.4) 100%);
  background-image: -webkit-linear-gradient(top,#000000 0%,rgba(30,50,30,0.4) 100%);
  background-image: -o-linear-gradient(top,#000000 0%,rgba(30,50,30,0.4) 100%);
  background-image: -ms-linear-gradient(top,#000000 0%,rgba(30,50,30,0.4) 100%);
  background-image: -webkit-gradient(linear,left top,left bottom,color-stop(0%,#000000),color-stop(100%,rgba(30,50,30,0.4)));
}

EOD;
        $css = new CM_Asset_Css($render, $css);
        $this->assertSame($expected, $css->get());
        //illegal parameters
        $css = <<<'EOD'
.foo {
	.gradient(vertical, foo, rgba(30, 50,30, 0.4));
	.gradient(vertical, #000000, foo);
	.gradient(horizontal, foo, rgba(30, 50,30, 0.4));
	.gradient(horizontal, #000000, foo);
	.gradient(foo, #000000, rgba(30, 50,30, 0.4));
}
EOD;
        $css = new CM_Asset_Css($render, $css);
        $this->assertSame('', $css->get());
    }

    public function testBoxSizing() {
        $render = new CM_Frontend_Render();
        $css = <<<'EOD'
.foo {
	.box-sizing(border-box);
}
EOD;
        $expected = <<<'EOD'
.foo {
  box-sizing: border-box;
  -moz-box-sizing: border-box;
  -webkit-box-sizing: border-box;
}

EOD;
        $css = new CM_Asset_Css($render, $css);
        $this->assertSame($expected, $css->get());
    }

    public function testUserSelect() {
        $render = new CM_Frontend_Render();
        $css = <<<'EOD'
.foo {
	.user-select(none);
}
EOD;
        $expected = <<<'EOD'
.foo {
  user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  -webkit-user-select: none;
}

EOD;
        $css = new CM_Asset_Css($render, $css);
        $this->assertSame($expected, $css->get());
    }

    public function testTransform() {
        $render = new CM_Frontend_Render();
        $css = <<<'EOD'
.foo {
	.transform(matrix(0.866,0.5,-0.5,0.866,0,0));
}
EOD;
        $expected = <<<'EOD'
.foo {
  transform: matrix(0.866,0.5,-0.5,0.866,0,0);
  -moz-transform: matrix(0.866,0.5,-0.5,0.866,0,0);
  -o-transform: matrix(0.866,0.5,-0.5,0.866,0,0);
  -ms-transform: matrix(0.866,0.5,-0.5,0.866,0,0);
  -webkit-transform: matrix(0.866,0.5,-0.5,0.866,0,0);
}

EOD;
        $css = new CM_Asset_Css($render, $css);
        $this->assertSame($expected, $css->get());
    }

    public function testTransition() {
        $render = new CM_Frontend_Render();
        $css = <<<'EOD'
.foo {
	.transition(width 2s ease-in 2s);
}
EOD;
        $expected = <<<'EOD'
.foo {
  transition: width 2s ease-in 2s;
  -moz-transition: width 2s ease-in 2s;
  -webkit-transition: width 2s ease-in 2s;
}

EOD;
        $css = new CM_Asset_Css($render, $css);
        $this->assertSame($expected, $css->get());
    }

    public function testMedia() {
        $render = new CM_Frontend_Render();
        $css = <<<'EOD'
.foo {
	color: blue;
	@media (max-width : 767px) {
		color: red;
	}
}
EOD;
        $expected = <<<'EOD'
.foo {
  color: blue;
}
@media (max-width: 767px) {
  .foo {
    color: red;
  }
}

EOD;
        $css = new CM_Asset_Css($render, $css);
        $this->assertSame($expected, $css->get());
    }
}
