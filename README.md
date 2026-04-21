Exemplo Prático Simples Docker Composer (Prof Hugo Rafael)

Agora, dentro da pasta onde está o docker-compose.yml, execute:

docker-compose up -d
Isso fará o PHP com Apache e o MySQL subirem juntos!

Para testar, acesse no navegador:
http://localhost:8080/

Se tudo estiver certo, você verá a mensagem:
"Conectado ao MySQL com sucesso!"

Se precisar parar os containers:

docker-compose down

---Explicação!---

./Dockerfile
Usa a imagem oficial PHP + Apache.

Instala a extensão mysqli para conectar ao MySQL.

Copia os arquivos da pasta src/ para dentro do servidor web no container.

Define a porta 80 para o Apache.

./docker-compose.yml

Define dois serviços: php-apache e mysql.

O MySQL cria um banco chamado meu_banco com o usuário e senha definidos.

O PHP depende do MySQL (depends_on).

Usa volumes para persistir os dados do banco mesmo se o container for removido.

Cria uma rede Docker chamada minha-rede para comunicação entre os containers.

Agora você tem um ambiente PHP + MySQL pronto para desenvolvimento! 
