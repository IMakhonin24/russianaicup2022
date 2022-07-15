<?php

use Debugging\Color;

class MyColor {
    public Color $lightBlue01;

    public Color $blue01;
    public Color $blue05;
    public Color $blue1;

    public Color $black01;
    public Color $black02;
    public Color $black05;
    public Color $black1;

    public Color $lightGreen01;

    public Color $green01;
    public Color $green02;
    public Color $green05;
    public Color $green1;

    public Color $white01;
    public Color $white05;
    public Color $white1;

    public Color $lightRed01;

    public Color $red01;
    public Color $red05;
    public Color $red1;

    public Color $aqua01;
    public Color $aqua05;
    public Color $aqua1;

    public Color $orange01;
    public Color $orange05;
    public Color $orange1;

    public Color $violet01;
    public Color $violet05;
    public Color $violet1;

    public Color $yellow01;
    public Color $yellow05;
    public Color $yellow1;

    public function __construct()
    {
        $this->lightBlue01 = new Color(0, 0, 50, 0.1);

        $this->blue01 = new Color(0, 0, 255, 0.1);
        $this->blue05 = new Color(0, 0, 255, 0.5);
        $this->blue1 = new Color(0, 0, 255, 1);

        $this->black01 = new Color(0, 0, 0, 0.1);
        $this->black02 = new Color(0, 0, 0, 0.2);
        $this->black05 = new Color(0, 0, 0, 0.5);
        $this->black1 = new Color(0, 0, 0, 1);

        $this->lightGreen01 = new Color(0, 50, 0, 0.1);

        $this->green01 = new Color(0, 255, 0, 0.1);
        $this->green02 = new Color(0, 255, 0, 0.2);
        $this->green05 = new Color(0, 255, 0, 0.5);
        $this->green1 = new Color(0, 255, 0, 1);

        $this->white01 = new Color(255, 255, 255, 0.1);
        $this->white05 = new Color(255, 255, 255, 0.5);
        $this->white1 = new Color(255, 255, 255, 1);

        $this->lightRed01 = new Color(50, 0, 0, 0.1);

        $this->red01 = new Color(255, 0, 0, 0.1);
        $this->red05 = new Color(255, 0, 0, 0.5);
        $this->red1 = new Color(255, 0, 0, 1);

        $this->aqua01 = new Color(0, 255, 255, 0.1);
        $this->aqua05 = new Color(0, 255, 255, 0.5);
        $this->aqua1 = new Color(0, 255, 255, 1);

        $this->orange01 = new Color(255, 102, 0, 0.1);
        $this->orange05 = new Color(255, 102, 0, 0.5);
        $this->orange1 = new Color(255, 102, 0, 1);

        $this->violet01 = new Color(128, 0, 255, 0.1);
        $this->violet05 = new Color(128, 0, 255, 0.5);
        $this->violet1 = new Color(128, 0, 255, 1);

        $this->yellow01 = new Color(255, 255, 0, 0.1);
        $this->yellow05 = new Color(255, 255, 0, 0.5);
        $this->yellow1 = new Color(255, 255, 0, 1);
    }
}