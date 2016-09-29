# php-afip

1) OBTENCION DEL CSR (Certificate Signig Request) 

Para obtener el CSR necesita la aplicación OpenSSL
Si su computadora tiene un sistema operativo Linux/Unix, seguramente ya lo tiene instalado. 

Una vez instalada dicha aplicación, abra el archivo /etc/ssl/openssl.cnf (El nombre y ruta de este archivo puede variar de acuerdo a la distribución Linux) con el TextPad (o cualquier otro procesador de texto), busque la sección  [req_distinguished_name] y agregue "serialNumber = Enter your CUIT"
El archivo debería quedar de la siguiente manera:

[req_distinguished_name]
countryName = CountryName (2 letter code)
countryName_dafuIt = GB
countryName_min = 2
countryName_rnax = 2
serialNumber = Enter your CUIT

Una vez grabados los cambios, abra una ventana de DOS/Terminal y posiciónese en C:\OpenSSL\Bin y o ruta similar en Linux y tipee los siguientes comandos respetando los espacios:

openssl genrsa -out privada 1024 (genera su clave privada)
openssl req -new -key privada -out pedido (genera el CSR)

Al ejecutar este Ultimo comando aparecerá un cuestionario que debe completar de la siguiente manera

Country Name: AR
State or Province: dejar en blanco (presione ENTER)
Locality Name: dejar en blanco (presione ENTER)
Organizational Name: {Nombre de Ia Empresa}
Organizational Unit: dejar en blanco (presione ENTER)
Common Name: {Un nombre de pila}
Email Address: dejar en blanco (presione ENTER)
Enter your CUIT: CUIT XXXXXXXXXXX (La palabra CUIT y a continuación el CUIT de Ia empresa que comenzará a utilizar el Webservice SIN guiones)
A Challenge Password: dejar en blanco (presione ENTER)
An Optional Company: dejar en blanco (presione ENTER)

De esta manera se habrán generado dos archivos denominados como: pedido y privada

El archivo privada es la clave privada generada en el computador y nunca debe publicarse por razones de seguridad.
El archivo pedido contiene una cadena de texto que a modo de CERTIFICATE REQUEST se debe enviar a la direccion de email: webservices@afip.gov.ar
2) ENVIO DEL CSR (Certificate Signig Request) 

El email deberia ser algo similar a esto:

Para:	        webservices@afip.gov.ar
Asunto:	Obtener certificado digital para test

Estimados, 

Copio el CSR para solicitarles el certificado digital para test, para la CUIT {xx-xxxxxxxx-x}

El webservice que necesito utilizar es WSFE

Muchas gracias!


-----BEGIN CERTIFICATE REQUEST-----
MIIBqTCCARICAQAwaTELMAkGA1UEBhMCQVIxGTAXBgNVBAUTEENVSVQgMzA2OTY2
NTc2NzYxEzARBgNVBAgMClNvbWUtU3RhdGUxEDAOBgNVBAoMB1RBIFRlc3QxGDAW
BgNVBAMMD0xlbTjOwxr4LmRvIEJpc2FybzCNBgI1hkiG9w0BAQEFAAOBjQAwgYkC
gYEA0pOONHcwmQ204hlTc1zJICjnuF3s3OX6GENhywwwlscCejKrQCdMLdTlmFhx
7YU5rRjug8sQ1noAwC4DiT9o40njwgMyIm6gbhPBfwHrABhLRpFb09JTY715oMMf
FRJAMxYeUFIIEJp2jZnAPZukIyNnZGCA8E8x2vI1mAxl8wsCAwEAAaAAMA0GCSqG
SIb3DQEBBQUAA4GBAHzUSXvvGQX7FGNplDVatwLBo2CxoYwoyc+fQMyLZApjfDVV
CB5llc3YcTjOwxr4L8rs+gwFvhS4/XUbyp+lpmhylmoXbTgbMq41HyttgbWzonCw
fYMuTI9iTUM4xaOLeVIGkIf6obAKu6PW54e4/x2gRUC1iclW0wj7H+OutCuy
-----END CERTIFICATE REQUEST-----


3) Una vez que AFIP responda 

Desde AFIP van a responder con un archivo que es en si el certificado digital (CRT)

Este archivo hay que guardarlo junto a los archivos: privada y pedido y con esos tres archivos y los scripts que te envie.
