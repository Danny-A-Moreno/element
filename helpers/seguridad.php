<?php

function generarCodigoVerificacion(): string {
    return (string) random_int(100000, 999999);
}
