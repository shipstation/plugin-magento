<?php

namespace Auctane\Api\Helper;

class Data
{
    /**
     * Dispatch webservice fault
     *
     * @param integer $code code
     * @param string $message message
     *
     * @return exception
     */
    public function fault($code, $message)
    {
        // if (is_numeric($code) && strlen((int) $code) === 3) {
        //     header(':', true, $code);
        // } else {
        //     header(':', true, 400);
        // }

        $message = mb_convert_encoding(
            str_replace('&', '&amp;', $message),
            'UTF-8'
        );

        $response = "<?xml version='1.0' encoding='UTF-8'?>";
        $response .= "<fault>";
        $response .= "\t\t<faultcode>" . $code . "</faultcode>\n";
        $response .= "\t\t<faultstring>" . $message . "</faultstring>\n";
        $response .= "</fault>";

        return $response;
    }
}
