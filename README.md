API ini adalah api yang menggunakan PHP Native yang menggunakan Token JWT

-Apabila Anda ingin Mencoba API ini. Langkah langkahnya adalah :

Download terlebih dahulu XAMPP
Jalankan Apache dan MySQL dalam XAMPP
Tekan Admin pada MySQL agar masuk ke dalam Database MariaDB
Buat database baru dengan nama "smartfarm"
Export File smartfarm.sql pada database "smartfarm" yang baru di buat.
Sebelum menjalankan data user,lahan,sensor dan data sensor pastikan menjalankan login terlebih dahulu agar mendapat token dengan endpoint :
http://localhost/smartfarm_api/login.php/
itu akan mengenerate token jwt
lalu pilih authorization  pada postman dengan memilih jenis token bearer token
kemudian isikan token dengan token yang telah didapat pada http://localhost/smartfarm_api/login.php/
Jalankan Endpoint Api Pada  Postman Contoh : http://localhost/smartfarm_api/users/ Untuk metode POST dan Delete Perlu menyertakan ID pada endpoint : http://localhost/smartfarm_api/users/5
