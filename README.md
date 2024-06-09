API ini adalah api yang menggunakan PHP Native yang menggunakan Token JWT

-Apabila Anda ingin Mencoba API ini. Langkah langkahnya adalah :

1. Download terlebih dahulu XAMPP
2. Jalankan Apache dan MySQL dalam XAMPP
3. Tekan Admin pada MySQL agar masuk ke dalam Database MariaDB
4. Buat database baru dengan nama "smartfarm"
5. Export File smartfarm.sql pada database "smartfarm" yang baru di buat.
6. Sebelum menjalankan data user,lahan,sensor dan data sensor pastikan menjalankan login terlebih dahulu agar mendapat token dengan endpoint :
7. http://localhost/smartfarm_api/login.php/
8. itu akan mengenerate token jwt
9. lalu pilih authorization  pada postman dengan memilih jenis token bearer token
10. kemudian isikan token dengan token yang telah didapat pada http://localhost/smartfarm_api/login.php/
11. Jalankan Endpoint Api Pada  Postman Contoh : http://localhost/smartfarm_api/users/ Untuk metode POST dan Delete Perlu menyertakan ID pada endpoint : http://localhost/smartfarm_api/users/5
